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
   private $key;
   // TokenRate is the rate of regeneration for the TokenBucket.
   private $rate;
   // Where the token bucket is to be stored
   private $backend;

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
    * Tries to consume a given number of tokens. Returns [$success, $time]
    * where $time is the number of seconds until the specified number tokens
    * will be available.
    *
    * @param amount is a numeric amount of tokens that will be attempted to be
    * consumed
    *
    * @return array index 0 being a boolean whether or not a token was consumed
    *
    *               index 1 being a double interval of seconds when there
    *               will be enough tokens to consume the amount requested.
    */
   public function consume($amount) {
      if (!is_numeric($amount) || $amount < 0) {
         throw new InvalidArgumentException("amount must be a non-negative number");
      }

      $storedBucket = $this->getStoredBucket();
      $tokens = $this->calculateCurrentTokens($storedBucket);

      $updatedTokens = $tokens - $amount;
      if ($updatedTokens < 0) {
         return [false, $this->readyTime($amount, $storedBucket)];
      }

      $newBucket = new StoredBucket($updatedTokens, $this->microtime());
      $this->storeBucket($newBucket);
      return [true, 0];
   }

   /**
    * @return $tokens int the total number of tokens in the bucket currently.
    */
   public function getTokens() {
      $storedBucket = $this->getStoredBucket();
      $updatedTokens = $this->calculateCurrentTokens($storedBucket);
      return $updatedTokens;
   }

   private function getStoredBucket() {
      $storedBucket = $this->backend->get($this->key);
      if ($storedBucket === Backend::MISS) {
         $storedBucket = new StoredBucket($this->rate->getTokens(),
          $this->microtime());
      }

      return $storedBucket;
   }

   /**
    * Updates tokens to the amount that it should be after restoring the tokens
    * that have been regenerated from the last consume to now.
    */
   private function calculateCurrentTokens(StoredBucket $stored) {
      $tokens = $stored->getTokens();
      $lastConsume = $stored->getLastConsume();

      $timeLapse = $this->microtime() - $lastConsume;
      if ($timeLapse == 0) {
         return $tokens;
      }

      $tokens += $timeLapse * $this->rate->getRate();
      // don't go over maximum tokens.
      return min($this->rate->getTokens(), $tokens);
   }

   /**
    * Returns the number of seconds until the request tokens will be available.
    *
    * If the amount requested is higher than the maximum amount of
    * tokens the bucket can hold then return null to show that it will never be
    * ready, since it can never regenerate up to that point.
    */
   private function readyTime($consumeAmount, StoredBucket $stored) {
      if (!is_int($consumeAmount)) {
         throw new InvalidArgumentException("amount must be an int");
      }

      if ($consumeAmount > $this->rate->getTokens()
       || $this->rate->getRate() <= 0) {
         return null;
      }

      $tokens = $this->calculateCurrentTokens($stored);

      return ($consumeAmount - $tokens) / $this->rate->getRate();
   }

   /**
    * Stores the current bucket which is the current number of tokens and the
    * last consume for the length of time it will take to regenerate all tokens
    * again.
    */
   private function storeBucket(StoredBucket $bucket) {
      $readyTime = $this->readyTime($this->rate->getTokens(), $bucket);

      $this->backend->set($this->key, $bucket, $readyTime);
   }

   protected function microtime() {
      return microtime(true);
   }
}
