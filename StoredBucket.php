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
         throw new InvalidArgumentException("lastConsume should be an int");
      }
      if (!is_int($tokens)) {
         throw new InvalidArgumentException("tokens should be an int");
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
