<?php


namespace App\Helpers\LAM;

/**
 * Class     TableEachFieldParser
 * 模版关于处理数据表的解析器
 * @package  App\Helpers\LAM
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class TableEachFieldParser extends CommonParser{

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 模版中循环语句替换对应的数据
	 * @example
	 * DummyTBForeach
	 *    if (Request::has('DummyTable.DTableFiled')) {
	 *        $query = $query->where('DummyTable.DTableFiled', '=', Request::input('DTableFiled'));
	 *    }
	 * EndDummyTBForeach
	 * @param array $tableArr 要生成的数据表信息
	 * @param $replacedIntro
	 * @param $type
	 * @return string
	 */
	public function replaceTableEach($tableArr, $replacedIntro, $type = '')
	{
		// 获取DummyTBForeach区间的内容模版，暂时只支持一个 DummyTBForeach
		$pa = '/DummyTBForeach.*?EndDummyTBForeach/s';
		preg_match($pa, $replacedIntro, $match);

		// 获取表名
		$tableName = $tableArr['name'] ?? '';
		// 获取表中的所有字段
		$tableFields = $tableArr['fields'] ?? [];

		// 去除循环模版标记
		$temp = str_replace(['EndDummyTBForeach','DummyTBForeach'], '', $match)[0];
		$finalFieldsRow = '';
		$tablePk = '';

		foreach ($tableFields as $k => $v){
			if(isset($v['pk']) && $v['pk']){
				$tablePk = $k;
			}
			if($type == 'res' && $k == 'created_at'){
				continue;
			}
			$finalFieldsRow .= str_replace(['DummyTable', 'DTableField'], [$tableName, $k], $temp);
		}

		// 将生成的所有字段的基础查询语句输出到模版中
		$replacedIntro = preg_replace($pa, $finalFieldsRow, $replacedIntro);
		// 将循环外部的 DummyTable 替换成表名
		$modelReplace = [
			'DummyTable' => $tableName,
			'DummyTPk' => $tablePk,
			'DummyTComment' => $tableArr['comment']
		];
		$replacedIntro = str_replace(array_keys($modelReplace), array_values($modelReplace), $replacedIntro);

		dd($type);
		// 将所有字段替换为内容模版并输出到内容区间中
		return $replacedIntro;
	}

}