<?

namespace iFixit\TokenBucket;

use \DateTime;
use \DateInterval;
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
         throw InvalidArgumentException("identifier must be a string");
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
    *               index 1 being a datetime object of when there will be
    *               enough tokens to consume the amount requested.
    */
   public function consume($amount) {
      if (!is_int($amount)) {
         throw InvalidArgumentException("amount must be an int");
      }

      list($tokens, $lastConsume) = $this->getTokenCountHelper();
      $tokens -= $amount;
      if ($tokens < 0) {
         return [false, $this->readyTime($amount, $tokens, $lastConsume)];
      }

      $now = new DateTime();
      $this->storeTokens($tokens, $now->getTimestamp());
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
      $now = (new DateTime());
      $storedBucket = $this->backend->get($this->key);
      if ($storedBucket === Backend::MISS) {
         return [$this->rate->tokens, $now];
      }

      return [$this->updateTokens($storedBucket->getTokens(),
       $storedBucket->getLastConsume()), $storedBucket->getLastConsume()];
      }

   /**
    * Updates tokens to the amount that it should be after restoring the tokens
    * that have been regenerated from the last consume to now.
    */
   private function updateTokens($tokens, DateTime $lastConsume) {
      if (!is_int($tokens)) {
         throw InvalidArgumentException("tokens must be an int");
      }

      $timeLapse = $lastConsume->diff(new DateTime())->s;
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
      if ($amount > $this->rate->tokens) {
         return null;
      }

      $readyDate = new DateTime();
      $ready = floor($this->rate->getRate() * ($amount - $tokens));
      // Don't go over int 32 bit signed int max for date times.
      $ready = (int)min(2147483647, $ready);
      $readyFromNow = new DateInterval("PT" . $ready . "S");
      return $readyDate->add($readyFromNow);
   }

   /**
    * Stores the bucket if the ready time is after now.
    */
   private function storeTokens($tokens, $lastConsume) {
      $readyTime = $this->readyTime($this->rate->tokens, $tokens, $lastConsume);
      if (!$readyTime) {
         return;
      }

      $now = new DateTime();
      $expireTime = $now->diff($readyTime)->s;

      $storedBucket = new StoredBucket($tokens, $now);
      $this->backend->set($this->key, $storedBucket, $expireTime);
   }
}
