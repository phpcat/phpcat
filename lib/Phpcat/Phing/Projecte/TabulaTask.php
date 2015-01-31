<?php

/**
 * Phpcat Phing Task
 * @package Phing
 * @subpackage Projecte
 */
namespace Phpcat\Phing\Projecte;

class TabulaTask extends \Phpcat\Phing\Task
{
	/**
	 * Curs del projecte enviat per paràmetre
	 * @var int
	 */
	private $curs = null;
	
    /**
     * Output 
     */
    private $returnProperty = null;

    public function setReturnProperty($val)
    {
        $this->returnProperty = $val;
    }

	public function setCurs($var)
    {
        $this->curs = (int) $var;


    }
	
    /**
     * The main entry point method.
     */
    public function main() {
		
	}
			

}
