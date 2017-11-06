<?php

namespace App\Helpers\LAM\Maker;

use Taoism\LAM\Maker\BaseMaker;

class CommonMaker implements BaseMaker
{
    protected $parsed = [];
    /**
     * 获取模板文件路径
     * @return mixed
     */
    public function getStub()
    {
        // TODO: Implement getStub() method.
    }

    /**
     * 设置由解析器解析的数组
     * @param $parsed
     * @return self
     */
    public function setParsed($parsed)
    {
        $this->parsed = $parsed;
    }

    /**
     * 开始生成文件
     * @return bool
     */
    public function makeFile()
    {
        // TODO: Implement makeFile() method.
    }

    /**
     * 获取解析器解析过的数组
     * @return array
     */
    public function getParsed()
    {
        return $this->parsed;
    }
}