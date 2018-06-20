<?php
namespace App\Helpers\LAM\Parser;

/**
 * Class     RepositoryParser
 * 仓库类解析器
 * @package  App\Helpers\LAM
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class RepositoryParser extends CommonParser
{
    /**
     * 仓库模版默认名称
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
     * 开始解析
     */
    public function beginParser()
    {
		$replaceArr = [];
		foreach ($this->getRawIntro() as $filePathName){
			// 根据服务名称获取需要生成的文件路径
			$needMakeFilePath = $this->getNeedMakeFilePath($filePathName);
			// 需要创建的文件夹路径
			$needMakeDir = $this->getPathDir($needMakeFilePath);
			// 基础类名 或 对应的 Model 名称
			$baseClassName = class_basename($filePathName);
			// 当前文件完整的类名
			$dummyClassName = $baseClassName.$this->getMakeType();

			// 需要替换 model 模版的 tag 及对应的值
			$tableModelNamespace = $this->getFileNamespace($filePathName, 'model');
			$replaceModelArr = [
				'DummyNamespace' => $tableModelNamespace,
				'DummyClassName' => $baseClassName,
				'DummyUsePath' => $tableModelNamespace.'\\'.$baseClassName,
				'DummyFilePathName' => $filePathName
			];
			// 生成相关 Model, 传入生成的 Model 路径
			$this->makeTableModel($this->getNeedMakeFilePath($filePathName, 'model'), $replaceModelArr);

			// 需要替换 res 模版的 tag 及对应的值
			$replaceArr[] = [
				'DummyNamespace' => $this->getFileNamespace($filePathName),
				'DummyClassName' => $dummyClassName,
				'DummyUsePath' => $this->getFileNamespace($filePathName).'\\'.$dummyClassName,
				'DummyModelName' => $baseClassName,
				'DummyModelUsePath' => $this->getFileNamespace($filePathName, 'model').'\\'.$baseClassName,
				'DummyFilePathName' => $filePathName,
				'DummyMore' => ''
			];

		}
        $this->setResult($replaceArr);
        return $this;
    }
}