<?

namespace iFixit\TokenBucket;

use \InvalidArgumentException;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenRate.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Backend.php';

/**
 * Implements the token bucket algorithm and stores tokens in storage.
 *
 * The rate of token regeneration is specified by the TokenRate class. This
 * also specifies the maximum number of tokens the bucket can hold.
 *
 * The current number of tokens is time sensative so it is not stored as a
 * member of the class, instead you will have to call getTokens() to calculate
 * and retrieve the current number of tokens.
 *
 * Last consume can possibly be changed by another concurrent request, and
 * therefore is also not stored in the Object's state and always retrieved from
 * storage when needed.
 */
class TokenBucket {
   // String to identify this bucket uniquely in storage.
   private $identifier;
   // TokenRate is when your parents try to ration your arcade spending.
   private $rate;
   // Where the token is to be stored.
   private $storage;

   /**
    * @param $identifier string to uniqely store the bucket.
    */
   public function __construct($key, Backend $backend, TokenRate $rate) {
      if (!is_string($key)) {
         throw new InvalidArgumentException("identifier must be a string");
      }

      $this->key = $key;
      $this->backend = $backend;
      $this->rate = $rate;
   }

   /**
    * Tries to consume a token, if a token can't be consumed, then a DateTime
    * is returned for when the next token will be available.
    *
    * @return array index 0 being a boolean whether or not a token was consumed
    *               index 1 being a timestamp of when there will be
    *               enough tokens to consume the amount requested.
    */
   public function consume($amount) {
      if (!is_int($amount)) {
         throw new InvalidArgumentException("amount must be an int");
      }

      $storedBucket = $this->backend->get($this->key);
      if ($storedBucket === Backend::MISS) {
         $storedBucket = new StoredBucket($this->rate->tokens, microtime(true));
      }
      $tokens = $storedBucket->getTokens();
      $lastConsume = $storedBucket->getLastConsume();

      $updatedTokens = $tokens - $amount;
      $now = microtime(true);
      $newBucket = new StoredBucket($updatedTokens, $now);
      if ($updatedTokens < 0) {
         return [false, $this->readyTime($amount, $newBucket)];
      }

      $this->storeBucket($newBucket);
      return [true, $now];
   }

   /**
    * @return $tokens int the total number of tokens in the bucket currently.
    */
   public function getTokens() {
      $storedBucket = $this->backend->get($this->key);
      if ($storedBucket === Backend::MISS) {
         return $this->rate->tokens;
      }

      $currentTokens = $storedBucket->getTokens();
      $lastConsume = $storedBucket->getLastConsume();
      $updatedTokens = $this->updateTokens($currentTokens, $lastConsume);
      return $updatedTokens;
   }

   /**
    * Updates tokens to the amount that it should be after restoring the tokens
    * that have been regenerated from the last consume to now.
    */
   private function updateTokens($tokens, $lastConsume) {
      if (!is_int($tokens)) {
         throw new InvalidArgumentException("tokens must be an int");
      }
      if (!is_double($lastConsume)) {
         throw new InvalidArgumentException("lastConsume must be a double");
      }

      $timeLapse = microtime(true) - $lastConsume;
      if ($timeLapse == 0) {
         return $tokens;
      }

      $tokens += floor($timeLapse * $this->rate->getRate());
      // don't go over maximum tokens.
      return min($this->rate->tokens, $tokens);
   }

   /**
    * Returns the number of seconds from when the amount requested will be
    * available.
    *
    * If the amount requested is higher than the maximum amouunt of
    * tokens the bucket can hold then return null to show that it will never be
    * ready, since it can never regenerate up to that point.
    */
   private function readyTime($consumeAmount, StoredBucket $stored) {
      if (!is_int($consumeAmount)) {
         throw new InvalidArgumentException("amount must be an int");
      }

      if ($consumeAmount > $this->rate->tokens) {
         return null;
      }

      $tokens = $stored->getTokens();
      return $this->rate->getRate() * ($consumeAmount - $tokens);
   }

   /**
    * Stores the current bucket which is the current number of tokens and the
    * last consume for the length of time it will take to regenerate all tokens
    * again.
    */
   private function storeBucket(StoredBucket $bucket) {
      $tokens = $bucket->getTokens();
      $lastConsume = $bucket->getLastConsume();
      $readyTime = $this->readyTime($this->rate->tokens, $bucket);

      $this->backend->set($this->key, $bucket, $readyTime);
   }
}
