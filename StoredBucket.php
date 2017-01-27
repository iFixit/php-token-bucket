<?

namespace iFixit\TokenBucket;

use \DateTime;

class StoredBucket {
   private $tokens;
   private $lastConsume;

   public function __construct($tokens, DateTime $lastConsume) {
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
