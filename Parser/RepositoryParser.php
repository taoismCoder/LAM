<?php
namespace App\Helpers\LAM\Parser;

/**
 * Class     RepositoryParser
 * 处理仓库类的解析器
 * @package  App\Helpers\LAM
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class RepositoryParser extends CommonParser
{
    /**
     * 基础模板名称
     * @var string
     */
    protected $baseStubName = 'res';

    /**
     * RepositoryParser constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 替换对应的便签
     */
    public function replace()
    {
        $intro = $this->getRawIntro();
        $stubContent = $this->getStubContents();
        $result = [];
        $this->setResult($result);
    }
}