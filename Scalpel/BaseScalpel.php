<?php
namespace Taoism\LAM\Scalpel;
use Taoism\LAM\Parser\BaseParser;

/**
 * 文件基础操作接口
 * Interface BaseScalpel
 * @package Taoism\LAM\Scalpel
 */
interface BaseScalpel {
	/**
	 * 获取文件内容
	 * @param BaseParser $parser
	 * @return mixed
	 */
	public function getContents(BaseParser $parser);
}