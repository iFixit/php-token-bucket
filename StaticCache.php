<?
require_once '../Matryoshka/library/iFixit/Matryoshka/Backend.php';
use iFixit\Matryoshka\Backend as Backend;

class StaticCache extends Backend {
   private static $cache = [];

   public function set($key, $value, $expiration = 0) {
      self::$cache[$key] = $value;
   }

   public function get($key) {
      return array_key_exists($key, self::$cache) ?
       self::$cache[$key] : Backend::MISS;
   }

   public function getMultiple(array $keys) {
      throw new Exception("Unimpplemented!");
   }
   public function decrement($key, $amount = 1, $expiration = 0) {
      throw new Exception("Unimpplemented!");
   }
   public function delete($key) {
      throw new Exception("Unimpplemented!");
   }
   public function setMultiple(array $values, $expiration = 0) {
      throw new Exception("Unimpplemented!");
   }
   public function add($key, $value, $expiration = 0) {
      throw new Exception("Unimpplemented!");
   }
   public function increment($key, $amount = 1, $expiration = 0) {
      throw new Exception("Unimpplemented!");
   }
   public function deleteMultiple(array $keys) {
      throw new Exception("Unimpplemented!");
   }
}
