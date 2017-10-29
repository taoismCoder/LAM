<?php
namespace Taoism\LAM\Maker;
/**
 * 生成器基础接口
 * Interface BaseMaker
 * @package Taoism\LAM\Maker
 */
interface BaseMaker {
    /**
	 * 获取模板文件路径
	 * @param $path
	 * @return mixed
	 */
	public function getStub($path);
}
