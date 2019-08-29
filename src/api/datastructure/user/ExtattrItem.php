<?php
namespace src\api\datastructure\user;

class ExtattrItem
{
	public $name = null;
	public $value = null;

	public function __construct($name = null, $value = null)
	{
		$this->name = $name;
		$this->value = $value;
	}
}