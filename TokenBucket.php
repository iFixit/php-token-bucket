<?

namespace iFixit\TokenBucket;

use \InvalidArgumentException;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenRate.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Backend.php';

/**
 * Implements the token bucket algorithm and stores tokens in storage.
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

      list($tokens, $lastConsume) = $this->getTokenCountHelper();
      $tokens -= $amount;
      if ($tokens < 0) {
         return [false, $this->readyTime($amount, $tokens, $lastConsume)];
      }

      $now = microtime(true);
      $this->storeTokens($tokens, $now);
      return [true, $now];
   }

   public function getTokenCount() {
      list($tokens, $_) = $this->getTokenCountHelper();
      return $tokens;
   }

   /**
    * @return array index 0 the number of tokens in the bucket
    *               index 1 the last consumtion of a token, this will be the
    *               current time if there wasn't a stored bucket.
    */
   private function getTokenCountHelper() {
      $storedBucket = $this->backend->get($this->key);
      if ($storedBucket === Backend::MISS) {
         return [$this->rate->tokens, microtime(true)];
      }

      return [$this->updateTokens($storedBucket->getTokens(),
       $storedBucket->getLastConsume()), $storedBucket->getLastConsume()];
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

      $timeLapse = microtime() - $lastConsume;
      if ($timeLapse == 0) {
         return $tokens;
      }

      $tokens += floor($timeLapse * $this->rate->getRate());
      // Don't go over int max if we're going to use this as an int.
      $tokens = (int)min(PHP_INT_MAX, $tokens);
      // don't go over maximum tokens.
      return min($this->rate->tokens, $tokens);
   }

   /**
    * Returns the DateTime when the amount requested will be available.
    * if the amount requested is higher than the max of the token bucket then
    * it will return null, since there will never be a ready time.
    */
   private function readyTime($amount, $tokens, $lastConsume) {
      if (!is_int($amount)) {
         throw new InvalidArgumentException("amount must be an int");
      }
      if (!is_int($tokens)) {
         throw new InvalidArgumentException("tokens must be an int");
      }
      if (!is_double($lastConsume)) {
         throw new InvalidArgumentException("lastConsume must be a double");
      }

      if ($amount > $this->rate->tokens) {
         return null;
      }

      $untilReady = floor($this->rate->getRate() * ($amount - $tokens));
      // Don't go overflow
      $untilReady = (int)min(PHP_INT_MAX, $untilReady);
      return $untilReady ;
   }

   /**
    * Stores the bucket if the ready time is after now.
    */
   private function storeTokens($tokens, $lastConsume) {
      if (!is_int($tokens)) {
         throw new InvalidArgumentException("tokens must be an int");
      }
      if (!is_double($lastConsume)) {
         throw new InvalidArgumentException("lastConsume must be a double");
      }

      $readyTime = $this->readyTime($this->rate->tokens, $tokens, $lastConsume);
      if (!$readyTime && $this->rate->getRate() != 0) {
         return;
      }

      $now = microtime(true);
      $expireTime = $now - $readyTime;

      $storedBucket = new StoredBucket($tokens, $now);
      $this->backend->set($this->key, $storedBucket, (int)round($expireTime));
   }
}
