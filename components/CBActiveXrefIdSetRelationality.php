<?php
/**
 * CBActiveSetRelationality.
 *
 * @since 1.3
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBActiveXrefIdSetRelationality extends CBActiveIndexedRelationality
{
	/**
	 * Class name of cross-reference model.
	 *
	 * @var string
	 */
	public $xrefClass;
	/**
	 * Relation name.
	 *
	 * @var string
	 */
	public $xrefRelationName;

	/**
	 * Attach behavior and add relations.
	 *
	 * @param CModel $owner
	 */
	public function attach($owner)
	{
		parent::attach($owner);

		$metadata = $this->owner->getMetadata();

		// Add many-many relation
		$metadata->addRelation($this->xrefRelationName, array(
			CActiveRecord::MANY_MANY,
			$this->xrefClass,
			$this->modelClass.'('.$this->ownerFkName.','.$this->indexFkName.')',
		));
	}
}
