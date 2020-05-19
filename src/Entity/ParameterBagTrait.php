<?php
namespace Drutiny\Entity;

trait ParameterBagTrait {
  protected $parameterBag;

  public function addParameters(array $parameters)
  {
      return $this->parameterBag->add($parameters);
  }

  public function setParameter($key, $value)
  {
      return $this->parameterBag->set($key, $value);
  }

  public function getParameter($key)
  {
      return $this->parameterBag->get($key);
  }

  public function getAllParameters()
  {
      return $this->parameterBag->all();
  }

  public function hasParameter($key)
  {
      return $this->parameterBag->has($key);
  }

  public function removeParameter($key)
  {
      return $this->parameterBag->remove($key);
  }

}
 ?>
