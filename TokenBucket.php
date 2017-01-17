<?

use iFixit\Matryoshka\Backend as Backend;

/**
 * Implements the token bucket algorithm and stores tokens in cache.
 */
class TokenBucket {
   // String to identify this bucket uniquely in cache.
   private $identifier;
   // TokenRate is when your parents try to ration your arcade spending.
   private $rate;
   // Where the token is to be stored.
   private $storage;

   /**
    * @param $identifier string to uniqely store the bucket.
    */
   public function __construct($key, Backend $backend, TokenRate $rate, $max) {
      if (!is_string($key)) {
         throw InvalidArgumentException("identifier must be a string");
      }
      if (!is_int($max)) {
         throw InvalidArgumentException("max should be an integer");
      }

      $this->key = $key;
      $this->backend = $backend;
      $this->rate = $rate;
      $this->max = $max;
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
      $lastConsumeDate = (new DateTime())->setTimestamp($lastConsume);

      $cached = $this->backend->get($this->key);
      if ($cached === null) {
         return [$this->max, $lastConsumeDate];
      }

      return [$this->updateTokens($tokens, $lastConsumeDate), $lastConsumeDate];
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
      return min($this->max, $tokens);
   }

   /**
    * Returns the DateTime when the amount requested will be available.
    * if the amount requested is higher than the max of the token bucket then
    * it will return null, since there will never be a ready time.
    */
   private function readyTime($amount, $tokens, $lastConsume) {
      if ($amount > $this->max) {
         return null;
      }

      $readyDate = new DateTime();
      $readyFromNow = new DateInterval(
       "PT" . $this->rate->getRate() * ($amount - $tokens) . "S");
      return $readyDate->add($readyFromNow);
   }

   /**
    * Stores the bucket if the ready time is after now.
    */
   private function storeTokens($tokens, $lastConsume) {
      $now = new DateTime();
      $readyTime = $this->readyTime($this->max, $tokens, $lastConsume);
      if (!$readyTime) {
         return;
      }

      $expireTime = $now->diff($readyTime)->s;

      $this->backend->set($this->key, [$tokens, $now->getTimestamp()],
       $expireTime);
   }
}
