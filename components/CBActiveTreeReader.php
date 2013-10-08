<?php
/**
 * CBActiveTreeReader.
 *
 * @since 1.3
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBActiveTreeReader
{
	/**
	 * Model finder.
	 *
	 * @var CActiveRecord
	 */
	private $modelFinder;

	/**
	 * Id column.
	 *
	 * @var string
	 */
	private $idColumn;

	/**
	 * Parent id column.
	 * @var string
	 */
	private $parentIdColumn;

	/**
	 * Result tree.
	 *
	 * @var CBTree
	 */
	private $tree;

	public function __construct($modelFinder, $idColumn, $parentIdColumn)
	{
		$this->modelFinder = $modelFinder;
		$this->idColumn = $idColumn;
		$this->parentIdColumn = $parentIdColumn;
	}

	public function getFullTree()
	{
		$this->tree = new \BogoTree\Mutable\Tree();

		$this->read(array(null));

		return $this->tree;
	}

	public function getSubtreeOf($rootModel)
	{
		$this->tree = $this->makeRoot($rootModel->{$this->parentIdColumn});

		$rootNodeId = $rootModel->{$this->idColumn};

		$this->tree->makeNode($rootModel, $rootNodeId);

		$this->read(array($rootNodeId));

		return $this->tree;
	}

	private function read($parentIds)
	{
		$this->modelFinder->dbCriteria->addInCondition($this->parentIdColumn, $parentIds);
		$foundModels = $this->modelFinder->findAll();

		$readNodeIds = array();
		foreach ($foundModels as $foundModel) {
			$nodeId = $foundModel->{$this->idColumn};
			$parentNodeId = $foundModel->{$this->parentIdColumn};

			$this->tree->makeNode($foundModel, $nodeId, $parentNodeId ?: null);

			$readNodeIds[] = $nodeId;
		}

		if (!empty($readNodeIds)) {
			$this->read($readNodeIds);
		}
	}
}
