<?php
/**
 * Traits are a mechanism for code reuse in single inheritance languages such as PHP.
 * A Trait is intended to reduce some limitations of single inheritance by enabling a developer to reuse sets of methods
 * freely in several independent classes living in different class hierarchies.
 * The semantics of the combination of Traits and classes is defined in a way which reduces complexity,
 * and avoids the typical problems associated with multiple inheritance and Mixins.
 *
 * @author Safarov Alisher, Harm Frielink, Nordhorn, Germany
 * @author Safarov Alisher<alisher.safarov@outlook.com>, Harm Frielink <harm@harmfrielink.nl>
 * @copyright 2009-2018 Safarov Alisher, Harm Frielink
 */

namespace IPTools;

/**
 * Trait for IPTools.
 *
 * Version:
 * - 1.0.1.0 - 17 Jan 2018 - Original version by Alisher Safarov.
 * - 1.0.1.1 - 16 Nov 2018 - Docu + Versioning.
 */
trait PropertyTrait {
   /**
    * Generic Getter.
    * @param  string  $name
    * @return mixed
    */
   public function __get($name) {
      if (method_exists($this, $name)) {
         return $this->$name();
      }

      foreach (array('get', 'to') as $prefix) {
         $method = $prefix . ucfirst($name);
         if (method_exists($this, $method)) {
            return $this->$method();
         }
      }

      trigger_error('Undefined property');
      return null;
   }

   /**
    * Generic setter.
    * @param string $name
    * @param mixed  $value
    */
   public function __set($name, $value) {
      $method = 'set' . ucfirst($name);
      if ( ! method_exists($this, $method)) {
         trigger_error('Undefined property');
         return;
      }
      $this->$method($value);
   }
} // trait
