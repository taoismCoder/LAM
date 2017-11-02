<?php

namespace App\Helpers\LAM\Maker;

use Storage;
use InvalidArgumentException;

/**
 * usage:
 * 		(new AutoMakeMigration())->setRawJsonFileName('exampleRaw.json')->makeMigration();
 * 		(new AutoMakeMigration())->setRawTxtArray($txtArr)->makeMigration();
 *
 * Class AutoMakeMigration
 * @package App\Helpers\LAM\Maker
 */
class AutoMakeMigration {
	/**
	 * 生成migration数据来源
	 * json文件 或　txt转化的数组 二选一
	 * @var
	 */
	protected $dataFrom = 'json';

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
	 * 原生json文件名(二选一)
	 * 用来生成migration
	 * @var
	 */
	protected $rawJsonFileName;

	/**
	 * 原生txt文件格式后的表数据数组(二选一)
	 * 用来生成migration
	 * @var
	 */
	protected $rawTxtArray;

	/**
	 * AutoMakeMigration constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return \Illuminate\Foundation\Application|mixed|\Illuminate\Support\Composer
	 */
	public function getComposer() {
		static $composer;
		if ($composer) {
			return $composer;
		}
		return $composer = app('Illuminate\Support\Composer')->setWorkingPath('../');
	}

	/**
	 * @param $fileName
	 * @return $this
	 */
	public function setRawJsonFileName($fileName) {
		$this->rawJsonFileName = $fileName;
		$this->dataFrom = 'json';
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRawJsonFileName() {
		return $this->rawJsonFileName;
	}

	/**
	 * @param $rawArr
	 * @return $this
	 */
	public function setRawTxtArray($rawArr) {
		$this->rawTxtArray = $rawArr;
		$this->dataFrom = 'txtArr';
		return $this;
	}

	/**
	 * @return array
	 */
	public function getRawTxtArray() {
		return $this->rawTxtArray;
	}

	/**
	 * 格式化数据表源文件
	 * 相当于parser操作
	 * @return $this
	 */
	public function setTableDetail() {
		$sourceData = $this->getMigrationSourceData();

		foreach ($sourceData as $tableName => $tableInfo) {
			//dd($tableName, $tableInfo, $tableInfo['field']);
			$tableFieldDetail = "";
			$tableFieldDetail .= "$" . "table->engine = 'InnoDB';" . PHP_EOL;

			$IndexArr = [];
			foreach ($tableInfo['field'] as $tableField) {
				$tempArr = [];
				$tempIndexArr = [];
				$tableFields = explode(',', $tableField);
				//dd($tableFields);
				if (in_array('type:index', $tableFields) || in_array('type:unique', $tableFields)) {
					foreach ($tableFields as $field) {
						list($key, $val) = explode(':', $field);
						$tempIndexArr[$key] = $val;
					}
					$IndexArr[] = $tempIndexArr;
					continue;
				}
				foreach ($tableFields as $field) {
					list($key, $val) = explode(':', $field);
					$tempArr[$key] = $val;
				}
				//dump($tempArr);

				$tableFieldDetail .= $this->makeField($tempArr);
			}
			foreach ($IndexArr as $indexField) {
				$tableFieldDetail .= $this->makeField($indexField);
			}
			//dd($tableDetail);

			$tableInfo['field'] = $tableFieldDetail;
			$this->tableDetail[$tableName] = $tableInfo;
		}
		//dump($this->getTableDetail());
		//return $this;
		//dd($this->getTableDetail());
	}

	/**
	 * @return mixed
	 */
	public function getTableDetail() {
		return $this->tableDetail;
	}

	/**
	 * 返回格式
	 * [
	 *        "table_name_1" => [
	 *            "field" => [
	 *                0 => "key:product_id,type:increments,comment:'自增ID'"
	 *                1 => "key:product_name,type:string,length:128,default:'',comment:'产品名称'"
	 *                2 => "key:product_price,type:decimal,length:6,places:2,default:0,unsigned:1,comment:'price'"
	 *                3 => "key:created_at,type:integer,default:0,unsigned:1,comment:'添加时间'"
	 *            ],
	 *            "migration" => "create_table_name_1_table",
	 *            "model" => "ParentDir/ModelName",
	 *            "repository" => "ParentDir/RepositoryName"
	 *        ],
	 *
	 *        "table_name_2" => [...]
	 * ]
	 *
	 * @return array
	 */
	public function getMigrationSourceData() {
		$distArr = [];
		if ($this->dataFrom == 'json') {
			$content = json_decode(Storage::get($this->getRawJsonFileName()), true);
			//dd($content, $content['table']);
			$distArr = $content['table'];
		} elseif ($this->dataFrom == 'txtArr') {
			$txtArr = $this->getRawTxtArray();
			foreach ($txtArr as $tableItem) {
				$tableName = $tableItem['name'];
				$tempArr = [
					"migration" => "create_" . $tableName . "_table",
					"model" => $tableItem['model'],
					"repository" => $tableItem['model'] . 'Repository',
				];

				foreach ($tableItem['fields'] as $keyName => $keyInfo) {
					$tempKeyInfo = "key:" . $keyName;
					foreach ($keyInfo as $k => $v) {
						$tempKeyInfo .= "," . $k . ":" . $v;
					}
					$tempArr['field'][] = $tempKeyInfo;
				}

				$distArr[$tableName] = $tempArr;
			}
		}

		return $distArr;
	}


	public function makeMigration() {
		$this->setTableDetail();
		$this->writeMigration();
		return true;
	}

	/**
	 * Write the migration file to disk.
	 *
	 * @return string
	 */
	protected function writeMigration() {
		$path = $this->getMigrationPath();

		foreach ($this->getTableDetail() as $tableName => $tableDetail) {
			$file = pathinfo($this->create($tableDetail['migration'], $path, $tableName), PATHINFO_FILENAME);
			$this->getComposer()->dumpAutoloads();
			echo $file . '生成完成<br/>';
		}

	}


	protected function create($name, $path, $table) {
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
	public function getMigrationPath() {
		return app()->databasePath() . DIRECTORY_SEPARATOR . 'migrations';
	}

	/**
	 * Get the full path name to the migration.
	 *
	 * @param  string $name
	 * @param  string $path
	 * @return string
	 */
	protected function getPath($name, $path) {
		return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
	}

	/**
	 * Get the date prefix for the migration.
	 *
	 * @return string
	 */
	protected function getDatePrefix() {
		return date('Y_m_d_His');
	}

	/**
	 * 根据类型和需要生成的模版类型获取模版路径
	 * @return string
	 */
	protected function getMigrationStub() {
		return __DIR__ . '/../stubs/create.migration.plain.stub';
	}

	public function getClassName($name) {
		return studly_case($name);
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param  string $path
	 * @param  string $contents
	 * @param  bool $lock
	 * @return int
	 */
	protected function put($path, $contents, $lock = false) {
		// 如果直接使用put方法创建文件，为了防止意外覆盖旧文件，则先备份旧文件
		if (file_exists($path)) {
			copy($path, $path . '_' . date('Y_m_d_H_i_s', time()));
		}
		return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
	}

	/**
	 * Populate the place-holders in the migration stub.
	 *
	 * @param  string $name
	 * @param  string $stub
	 * @param  string $table
	 * @return string
	 */
	public function populateStub($name, $stub, $table) {
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
	protected function makeField($fieldProps) {
		if ($fieldProps['type'] == 'int') {
			$fieldProps['type'] = 'integer';
		}  elseif ($fieldProps['type'] == 'tinyInt') {
			$fieldProps['type'] = 'tinyInteger';
		}  elseif ($fieldProps['type'] == 'pk') {
			$fieldProps['type'] = 'increments';
		}

		$hasOneLengthField = [
			'char', 'string'
		];

		$hasTwoLengthField = [
			'decimal', 'double', 'float'
		];

		$numberField = [
			'integer', 'bigInteger', 'decimal', 'float', 'double'
		];

		$indexField = [
			'index', 'unique'
		];

		$field = '';

		if (in_array($fieldProps['type'], $indexField)){
			if (strpos($fieldProps['key'], '+')) {
				$indexField = implode('\',\'', explode('+', $fieldProps['key']));
				$fieldProps['key'] = "['" . $indexField . "']";
				$field .= "$" . "table->" . $fieldProps['type'] . "(" . $fieldProps['key'] . ")";
			} else {
				$field .= "$" . "table->" . $fieldProps['type'] . "('" . $fieldProps['key'] . "')";
			}
		} else {
			if (in_array($fieldProps['type'], $hasOneLengthField) && $fieldProps['length']) {
				$field .= "$" . "table->" . $fieldProps['type'] . "('" . $fieldProps['key'] . "'," . $fieldProps['length'] . ")";
			} elseif (in_array($fieldProps['type'], $hasTwoLengthField) && $fieldProps['length'] && $fieldProps['places']) {
				$field .= "$" . "table->" . $fieldProps['type'] . "('" . $fieldProps['key'] . "'," . $fieldProps['length'] . "," . $fieldProps['places'] . ")";
			} else {
				$field .= "$" . "table->" . $fieldProps['type'] . "('" . $fieldProps['key'] . "')";
			}
		}
		unset($fieldProps['type'], $fieldProps['key']);

		if (array_key_exists('default', $fieldProps)) {
			$field .= "->default(" . $fieldProps['default'] . ")";
			unset($fieldProps['default']);
		}

		if (array_key_exists('unsigned', $fieldProps) && in_array($fieldProps['type'], $numberField)) {
			if ($fieldProps['unsigned']) {
				$field .= "->unsigned()";
			}
		}
		unset($fieldProps['unsigned']);

		foreach ($fieldProps as $remainKey => $remainField) {
			if ($remainKey == 'comment') continue;
			$field .= "->" . $remainKey. "('" . $remainField . "')";
		}

		if (array_key_exists('comment', $fieldProps)) {
			$field .= "->comment(" . $fieldProps['comment'] . ")";
		}

		$field .= ";" . PHP_EOL;
		return $field;
	}

}