<?php namespace App\Helpers\LAM\Maker;

/**
 * Class RepositoryMaker
 * 处理数据表的生成器
 * @package  App\Helpers\LAM\Maker
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class RepositoryMaker extends CommonMaker {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 获取仓库类的根命名空间
	 * @return string
	 */
	protected function getRepositoryNamespace()
	{
		$rootNamespace = trim(app()->getNamespace(), '\\');
		return $rootNamespace.'\Repository';
	}

	public function makeFile()
	{
		foreach ($this->getParsed() as $filePathName){
			// 判断文件是否存在，其中包含根据服务名称获取要生成的文件路径
			$fileIsExists = $this->alreadyExists($filePathName);
			if(!$fileIsExists){
				// 获取 Repository 生成模版
				$stubPath = $this->getStub($this);

				// 根据服务名称获取需要生成的文件路径
				$needMakeFilePath = $this->getNeedMakeFilePath($filePathName);
				// 需要创建的文件夹路径
				$needMakeDir = $this->getPathDir($needMakeFilePath);

				// 替换模版中的名称及相关信息
				$finalContents = $this->replaceStubTags($stubPath, $this->getParsed());
				// 判断文件夹是否存在，不存在则创建文件夹
				if(!is_dir($needMakeDir)){
					mkdir($needMakeDir, 0755, true);
				}
				// 输出文件
				$this->put($needMakeFilePath, $finalContents);
				echo $needMakeFilePath.' 执行完毕<br/>';
			}
		}

	}
}