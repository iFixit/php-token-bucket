<?

namespace iFixit\TokenBucket;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenBucket.php';

/**
 * Test class used to override parts of TokenBucket so it can be adjusted for
 * use in tests.
 *
 * The microtime function is overrided to use an offset so that time can be
 * changed in the test without having to modify the time of the environment.
 */
class TestTokenBucket extends TokenBucket {
   // Used to offset the time of microtime microtime()
   private $offset = 0;

   protected function microtime() {
      return microtime(true) + $this->offset;
   }

   public function setOffset($offset) {
      if (!is_numeric($offset)) {
         throw InvalidArgumentException("offset needs to be numeric");
      }

      $this->offset = $this->offset;
   }
}
