<?php


namespace App\Helpers\LAM;

/**
 * Class     TableEachFieldParser
 *
 * @package  App\Helpers\LAM
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class TableEachFieldParser {

	/**
	 * 模版中循环语句替换对应的数据
	 * @example
	DummyTBForeach
	 * if (Request::has('DummyTable.DTableFiled')) {
	 * $query = $query->where('DummyTable.DTableFiled', '=', Request::input('DTableFiled'));
	 * }
	 * EndDummyTBForeach
	 * @param $dummyTableName
	 * @return string
	 */
	public function replaceTableEach($dummyTableName)
	{
		// 表中的所有字段
		$tableFileds = [];
		// 获取DummyTBForeach区间的内容模版
		// 将所有字段替换为内容模版并输出到内容区间中
		return '';
	}

}