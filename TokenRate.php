<?

/**
 * Defines a rate limit for token bucketing. Specify the tokens you want to
 * allow and then the time span in which that amount is allowed to occur in.
 */
class TokenRate {
   public $tokens;
   public $interval;

   public function __construct($tokens, DateInterval $interval) {
      if (!is_int($tokens)) {
         throw InvalidArgumentException("Tokens must be an int");
      }

      $this->tokens = $tokens;
      $this->interval = $interval;
   }

   /**
    * @return double tokens per interval
    */
   public function getRate() {
      if ($this->interval->s == 0 || $this->tokens == 0) {
         return 0;
      }

      $rate = (double)$this->tokens / (double)$this->interval->s;
      return $this->interval->invert ? -$rate : $rate;
   }
}
