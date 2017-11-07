<?php
namespace Taoism\LAM\Parser;
/**
 * 解析器基础接口
 * Interface BaseParser
 * @package Taoism\LAM\Parser
 */
interface BaseParser {
    /**
     * 设置要解析的内容
     * @param $intro
     * @return self
     */
    public function setRawIntro($intro);

    /**
     * 获取设置的解析内容
     * @return mixed
     */
    public function getRawIntro();

    /**
     * 获取解析后的数组
     * @return array
     */
    public function getResult();

    /**
     * 设置解析后的值
     * @param $result
     * @return array
     */
    public function setResult($result);
}