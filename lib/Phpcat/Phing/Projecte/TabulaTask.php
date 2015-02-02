<?php

/**
 * Phpcat Phing Task
 * @package Phing
 * @subpackage Projecte
 */

namespace Phpcat\Phing\Projecte;

/**
 * Class TabulaTask
 * 
 * Tasca Phing per tabular els projectes.
 */
class TabulaTask extends \Phpcat\Phing\Task
{

	/**
	 * Curs del projecte enviat per paràmetre
	 * 
	 * @var int
	 */
	private $curs = null;

	/**
	 * Arxiu on s'ha d'inserir el càlcul
	 * 
	 * @var string
	 */
	private $filename = null;

	/**
	 * Output 
	 */
	private $returnProperty = null;

	/**
	 * carpeta on s'ha fet el git clone
	 * @var string
	 */
	protected $repoFolder;

	/**
	 * branques processables
	 * 
	 * @var array
	 */
	protected $branches = [];

	/**
	 * informació de la branca
	 * 
	 * @var array
	 */
	protected $branchesInfo = [];

	/**
	 * Text que s'ha de substituir
	 * 
	 * @var string
	 */
	protected $textTabulat = '';

	/**
	 * Array per controlar si algú es passa dels 40 suports.
	 * 
	 * @var array
	 */
	protected $controlSuports = [];

	/**
	 * Ens guardem la carpeta anterior per a quan ens anem movent
	 * entre carpetes.
	 * 
	 * @var string
	 */
	protected $previousPath;

	const MARK = 'PhingProjecteTabula';

	public function setReturnProperty($val)
	{
		$this->returnProperty = $val;
	}

	/**
	 * @param int $var
	 */
	public function setCurs($var)
	{
		$this->curs = (int) $var;
	}

	/**
	 * @param string $var
	 */
	public function setFilename($var)
	{
		$this->filename = $var;
	}

	/**
	 * El punt d'entrada de la tasca Phing
	 */
	public function main()
	{

		$this->setProperties();

		$this->getBranches();

		$this->processBranchesToTabulate();

		if (isset($this->filename)) {
			$this->processFileName();
		}
	}

	protected function processFileName()
	{
		$fileContent = file_get_contents($this->filename, null, null, null, 65536);

		$cmd = '[//]: # (phing projecte.tabula -Dcurs='
				. $this->curs
				. ' -Dfilename='
				. $this->filename
				. ')';

		$pos = strpos($fileContent, $cmd);

		if (!($pos > 1)) {
			return false;
		}

		$pos += strlen($cmd);
		/**
		 * busquem la marca d'inici i de final
		 */
		$markInici = '[//]: # (' .
				self::MARK .
				'Inici)';
		$markFinal = '[//]: # (' .
				self::MARK .
				'Final)';

		$posIni = strpos($fileContent, $markInici, $pos);
		if ($posIni === false) {
			$posIni = $PosFi = $pos;
		} else {
			$posFi = strpos($fileContent, $markFinal, $posIni);
			$posFi += strlen($markFinal) +1 ;
		}

		$fileContentIni = substr($fileContent, 0, $posIni);
		$fileContentFi = substr($fileContent, $posFi);

		$fileContent = $fileContentIni
				. $markInici
				. PHP_EOL
				. $this->textTabulat
				. PHP_EOL
				. $markFinal
				. PHP_EOL
				. PHP_EOL;

		file_put_contents($this->filename, $fileContent);
	}

	protected function setProperties()
	{
		$this->repoFolder = $this
						->project
						->getProperty('projectes.temp.dir')
				. $this->curs;
	}

	protected function getBranches()
	{
		$this->changeToGitRepoFolder();

		exec("git branch -a", $branches);

		foreach ($branches as $branch) {
			$branch = trim($branch);
			if (substr($branch, 0, 23) == 'remotes/origin/feature/') {
				$this->branches[] = $branch;
			}
		}

		$this->returnFromGitFolder();
	}

	protected function processBranchesToTabulate()
	{
		if (empty($this->branches)) {
			return;
		}

		foreach ($this->branches as $branch) {
			$this->processBranch($branch);
		}

		$this->setTextTabulat();
	}

	protected function setTextTabulat()
	{
		$this->textTabulat = "\n\n"
				. "----\n\n"
				. "###Projectes Proj{$this->curs}\n\n";

		$this->textTabulat .= "Projecte|Descripció|Estimació|Suports\n";
		$this->textTabulat .= "--------|----------|--------:|------:\n";

		foreach ($this->branchesInfo as $gitProject => $info) {
			$this->textTabulat .= 
					"[{$gitProject}](https://github.com/phpcat/Proj{$this->curs}/tree/feature/{$gitProject})"
					. "|{$info['titol']}"
					. "|{$info['estimacio']}"
					. "|{$info['suports']}"
					. "\n";
		}


		$this->textTabulat .= "\n\n"
				. "----\n\n"
				. "###Developers Proj{$this->curs}\n\n";

		$this->textTabulat .= "Developer|Dedicació\n";
		$this->textTabulat .= "---------|--------:\n";
		foreach ($this->controlSuports as $developer => $dedicacio) {
			$this->textTabulat .=
					"[{$developer}](https://github.com/{$developer})"
					. "|{$dedicacio}"
					. "\n";
		}

		$this->textTabulat .= "\n\n";
	}

	protected function processBranch($branch)
	{
		$this->changeToGitRepoFolder();

		$gitBranch = basename($branch);
		exec("git checkout feature/{$gitBranch}");

		$info = [];

		$replaceIn = ['#', "\n"];
		$replaceOut = ['', ''];
		$info['titol'] = str_replace(
				$replaceIn, $replaceOut, $this->getFirsLineFromFile('README.md'));
		$info['estimacio'] = (int) $this->getFirsLineFromFile('estimacio');
		$info['suports'] = $this->getSuports();

		$this->branchesInfo[$gitBranch] = $info;

		$this->returnFromGitFolder();
	}

	protected function getSuports()
	{
		$suports = 0;

		$arxius = glob('suports/*');

		foreach ($arxius as $arxiu) {
			$suport = (int) $this->getFirsLineFromFile($arxiu);

			$suports += $suport;

			$developer = basename($arxiu);

			if (isset($this->controlSuports[$developer])) {
				$this->controlSuports[$developer] += $suport;
			} else {
				$this->controlSuports[$developer] = $suport;
			}
		}

		return $suports;
	}

	protected function changeToGitRepoFolder()
	{
		$this->previousPath = realpath('.');
		chdir($this->repoFolder);
	}

	protected function returnFromGitFolder()
	{
		chdir($this->previousPath);
		$this->previousPath = null;
	}

	protected function getFirsLineFromFile($file)
	{
		$handle = @fopen($file, "r");
		if ($handle) {
			$return = fgets($handle, 4096);
			fclose($handle);
		} else {
			$return = false;
		}

		return $return;
	}

}
