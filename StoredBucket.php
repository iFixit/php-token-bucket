<?

namespace iFixit\TokenBucket;

use \InvalidArgumentException;

class StoredBucket {
   private $tokens;
   private $lastConsume;

   /**
    * @param $lastConsume number of seconds since the last consumption.
    */
   public function __construct($tokens, $lastConsume) {
      if (!is_numeric($lastConsume)) {
         throw new InvalidArgumentException("lastConsume should be numeric");
      }
      if (!is_numeric($tokens) || $tokens < 0) {
         throw new InvalidArgumentException("tokens must be a non-negative number");
      }

      $this->tokens = $tokens;
      $this->lastConsume = $lastConsume;
   }

   public function getTokens() {
      return $this->tokens;
   }

   public function getLastConsume() {
      return $this->lastConsume;
   }
}
