<?php

namespace App\Helpers\LAM;

use Storage;
use InvalidArgumentException;

class AutoMakeMigration
{
	/**
	 * The Composer instance.
	 *
	 * @var \Illuminate\Support\Composer
	 */
	protected $composer;

	/**
	 * 表字段详情
	 * @var
	 */
	protected $tableDetail;

	/**
	 * AutoMakeMigration constructor.
	 */
	public function __construct()
	{
		$this->composer = app('Illuminate\Support\Composer');
	}

	/**
	 * @return \Illuminate\Foundation\Application|mixed|\Illuminate\Support\Composer
	 */
	public function getComposer()
	{
		static $composer;
		if ($composer) {
			return $composer;
		}
		return $composer = app('Illuminate\Support\Composer')->setWorkingPath('../');
	}

	/**
	 * 格式化数据表源文件
	 * 相当于parser操作
	 * @param $rawFileName
	 * @return $this
	 */
	public function setTableDetail($rawFileName)
	{
		$content = json_decode(Storage::get($rawFileName), true);
		//dd($content['table']);
		foreach($content['table'] as $tableName => $tableInfo) {
			//dump($tableName, $tableInfo, $tableInfo['field']);
			$tableFieldDetail = "";
			$tableFieldDetail .= "$" . "table->engine = 'InnoDB';" . PHP_EOL;

			foreach($tableInfo['field'] as $tableField) {
				$tempArr = [];
				foreach(explode(',', $tableField) as $field) {
					list($key, $val) = explode(':', $field);
					$tempArr[$key] = $val;
				}
				//dump($tempArr);

				$tableFieldDetail .= $this->makeField($tempArr);
			}
			//dd($tableDetail);
			$tableInfo['field'] = $tableFieldDetail;
			$this->tableDetail[$tableName] = $tableInfo;
		}
		//dump($this->getTableDetail());
		return $this;
		//dd($this->getTableDetail());
	}

	/**
	 * @return mixed
	 */
	public function getTableDetail()
	{
		return $this->tableDetail;
	}


	public function makeMigration()
	{
		$this->writeMigration();
		return true;
	}

	/**
	 * Write the migration file to disk.
	 *
	 * @return string
	 */
	protected function writeMigration()
	{
		$path = $this->getMigrationPath();

		foreach($this->getTableDetail() as $tableName => $tableDetail) {
			$file = pathinfo($this->create($tableDetail['migration'], $path, $tableName), PATHINFO_FILENAME);
			echo $file . '生成完成<br/>';
			$this->composer->dumpAutoloads();
		}

	}


	protected function create($name, $path, $table)
	{
		if (class_exists($className = $this->getClassName($name))) {
			throw new InvalidArgumentException("A $className migration already exists.");
		}
//dd($this->getClassName($name), class_exists($className = $this->getClassName($name)));
		$path = $this->getPath($name, $path);

		$stub = $this->getMigrationStub();
		//dd($stub);

		$this->put($path, $this->populateStub($name, $stub, $table));

		return $path;
	}


	/**
	 * Get migration path (either specified by '--path' option or default location).
	 *
	 * @return string
	 */
	public function getMigrationPath()
	{
		return app()->databasePath().DIRECTORY_SEPARATOR.'migrations';
	}

	/**
	 * Get the full path name to the migration.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @return string
	 */
	protected function getPath($name, $path)
	{
		return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
	}

	/**
	 * Get the date prefix for the migration.
	 *
	 * @return string
	 */
	protected function getDatePrefix()
	{
		return date('Y_m_d_His');
	}

	/**
	 * 根据类型和需要生成的模版类型获取模版路径
	 * @return string
	 */
	protected function getMigrationStub()
	{
		return __DIR__.'/stubs/create.migration.plain.stub';
	}

	public function getClassName($name)
	{
		return studly_case($name);
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string  $contents
	 * @param  bool  $lock
	 * @return int
	 */
	protected function put($path, $contents, $lock = false)
	{
		// 如果直接使用put方法创建文件，为了防止意外覆盖旧文件，则先备份旧文件
		if(file_exists($path)){
			copy($path, $path.'_'.date('Y_m_d_H_i_s', time()));
		}
		return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
	}

	/**
	 * Populate the place-holders in the migration stub.
	 *
	 * @param  string  $name
	 * @param  string  $stub
	 * @param  string  $table
	 * @return string
	 */
	public function populateStub($name, $stub, $table)
	{
		$fileContents = file_get_contents($stub);
		$fileContents = str_replace('DummyClass', $this->getClassName($name), $fileContents);
		$fileContents = str_replace('DummyTable', $table, $fileContents);
		$fileContents = str_replace('DummyDetail', $this->getTableDetail()[$table]['field'], $fileContents);

		return $fileContents;
	}

	/**
	 * 根据格式化数据表源文件,生成migration语句
	 * @param $fieldProps
	 * @return string
	 */
	protected function makeField($fieldProps)
	{
		$hasOneLengthField = [
			'char', 'string'
		];

		$hasTwoLengthField = [
			'decimal', 'double', 'float'
		];

		$numberField = [
			'integer', 'bigInteger', 'decimal', 'float', 'double'
		];

		//$table->string('status_message', 256)->default('')->comment('状态消息');
		//$table->string('message_id', 64)->default('')->comment('第三方ID');
		//$table->integer('created_at')->default(0)->unsigned()->comment('发送时间');
		$field = '';
		if (in_array($fieldProps['type'], $hasOneLengthField) && $fieldProps['length']) {
			$field .= "$" . "table->". $fieldProps['type'] ."('". $fieldProps['key'] ."',". $fieldProps['length'] .")";
		} elseif (in_array($fieldProps['type'], $hasTwoLengthField) && $fieldProps['length'] && $fieldProps['places']) {
			$field .= "$" . "table->". $fieldProps['type'] ."('". $fieldProps['key'] ."',". $fieldProps['length'] .",". $fieldProps['places'] .")";
		} else {
			$field .= "$" . "table->". $fieldProps['type'] ."('". $fieldProps['key'] ."')";
		}

		if (array_key_exists('default', $fieldProps)) {
			$field .= "->default(". $fieldProps['default'] .")";
		}

		if (array_key_exists('unsigned', $fieldProps) && in_array($fieldProps['type'], $numberField)) {
			if($fieldProps['unsigned']){
				$field .= "->unsigned()";
			}
		}

		if (array_key_exists('comment', $fieldProps)) {
			$field .= "->comment(". $fieldProps['comment'] .")";
		}

		$field .= ";" . PHP_EOL;
		return $field;
	}

}