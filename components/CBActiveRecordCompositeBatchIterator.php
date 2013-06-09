<?php
/**
 * Searches records in batches.
 *
 * @since 1.1
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBActiveRecordCompositeBatchIterator implements Iterator
{
	/**
	 * Source model.
	 *
	 * @var CBActiveRecord
	 */
	private $searchModel;

	/**
	 * Number of items to request per batch.
	 *
	 * @var integer
	 */
	private $batchSize;

	/**
	 * One-based index of current batch being processed.
	 *
	 * @var integer
	 */
	private $currentBatchKey;

	/**
	 * Current batch of items to be processed.
	 *
	 * @var CBActiveRecord[]
	 */
	private $currentBatchItems;

	/**
	 * Is current batch valid for processing?
	 *
	 * @var boolean
	 */
	private $isCurrentBatchValid;

	/**
	 * Is it worth executing another query to retrieve next batch?
	 *
	 * @var boolean
	 */
	private $moreBatchesMayExist;

	/**
	 * Number of items retrieved so far.
	 *
	 * @var integer
	 */
	private $retrievedItemCount;

	/**
	 * Number of items to request per batch.
	 *
	 * @return integer
	 */
	public function getBatchSize()
	{
		return $this->batchSize;
	}

	/**
	 * Number of items to request per batch.
	 *
	 * It's ok to change the batch size during the iteration.
	 *
	 * @param integer
	 */
	public function setBatchSize($batchSize)
	{
		$batchSize = intval($batchSize);

		if ($batchSize <= 0) {
			throw new CException('Batch size must be a positive integer');
		}

		$this->batchSize = $batchSize;
	}

	/**
	 * Number of items retrieved so far.
	 *
	 * @return integer
	 */
	public function getRetrievedItemCount()
	{
		return $this->retrievedItemCount;
	}

	/**
	 * Initialize.
	 *
	 * @param CBActiveRecord $searchModel
	 * @param integer $batchSize
	 */
	public function __construct(CBActiveRecord $searchModel, $batchSize)
	{
		$this->searchModel = $searchModel;
		$this->setBatchSize($batchSize);
	}

	/**
	 * Current batch of items to be processed.
	 *
	 * @return CBActiveRecord[]
	 */
	public function current()
	{
		return $this->currentBatchItems;
	}

	/**
	 * Zero-based index of current batch being processed.
	 *
	 * @return integer
	 */
	public function key()
	{
		return ($this->currentBatchKey - 1);
	}

	/**
	 * Retrieve next batch of items if it's possible one exists.
	 */
	public function next()
	{
		if ($this->moreBatchesMayExist) {
			// Try to get next batch of items

			// Update criteria according to match current batch key and size
			$searchModelCriteria = $this->searchModel->getDbCriteria();
			$searchModelCriteria->offset = $this->currentBatchKey * $this->batchSize;
			$searchModelCriteria->limit = $this->batchSize;

			// Retrieve items
			$this->currentBatchItems = $this->searchModel->findAll();
			$this->currentBatchKey++;

			// Check the results
			$batchItemCount = count($this->currentBatchItems);
			$this->retrievedItemCount += $batchItemCount;

			if ($batchItemCount === 0) {
				// We received nothing
				$this->isCurrentBatchValid = false;
				$this->moreBatchesMayExist = false;
			} else {
				// We did receive data, but is it full?
				$this->isCurrentBatchValid = true;
				$this->moreBatchesMayExist = ($batchItemCount == $this->batchSize);
			}
		}
	}

	/**
	 * Start over.
	 */
	public function rewind()
	{
		$this->currentBatchKey = 0;
		$this->moreBatchesMayExist = true;
		$this->retrievedItemCount = 0;
		$this->next();
	}

	/**
	 * Is current batch valid for processing?
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return $this->isCurrentBatchValid;
	}
}