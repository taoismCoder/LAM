<?php
namespace App\Helpers\LAM\Parser;

/**
 * Class     RepositoryParser
 * �����ֿ���Ľ�����
 * @package  App\Helpers\LAM
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class RepositoryParser extends CommonParser
{
    /**
     * ����ģ������
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
     * �滻��Ӧ�ı�ǩ
     */
    public function replace()
    {
        $intro = $this->getRawIntro();
        $stubContent = $this->getStubContents();
        $result = [];
        $this->setResult($result);
    }
}