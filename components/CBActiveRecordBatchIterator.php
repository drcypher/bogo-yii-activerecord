<?php
/**
 * Searches records in batches.
 *
 * @since 1.1
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBActiveRecordBatchIterator implements Iterator
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
	 * Local key of item processed in current batch.
	 *
	 * @var integer
	 */
	private $currentBatchItemKey;

	/**
	 * Total count of items in current batch.
	 *
	 * @var integer
	 */
	private $currentBatchItemCount;

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
	 * Number of items processed so far.
	 *
	 * @var integer
	 */
	private $processedItemCount;

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
	 * It's ok to change the batch size during the iteration. Still, changes in the batch size
	 * will become effective when a new batch retrieval will be attempted.
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
		return $this->currentBatchItems[$this->currentBatchItemKey];
	}

	/**
	 * Zero-based index of current batch being processed.
	 *
	 * @return integer
	 */
	public function key()
	{
		return $this->processedItemCount;
	}

	/**
	 * Retrieve next batch of items if it's possible one exists.
	 */
	public function next()
	{
		// Proceed to next item in current batch
		$this->currentBatchItemKey++;
		$this->processedItemCount++;

		if ($this->currentBatchItemKey >= $this->currentBatchItemCount) {
			// We just exceeded the capacity of the current batch. Time to look for the next.
			if ($this->moreBatchesMayExist) {
				// Try to get next batch of items
				$this->fetchNextBatch();
			}
		}
	}

	/**
	 * Attempt to retrieve a batch from the database.
	 */
	private function fetchNextBatch()
	{
		// Update criteria according to match current batch key and size
		$searchModelCriteria = $this->searchModel->getDbCriteria();
		$searchModelCriteria->offset = $this->currentBatchKey * $this->batchSize;
		$searchModelCriteria->limit = $this->batchSize;

		// Retrieve items
		$this->currentBatchItems = $this->searchModel->findAll();
		$this->currentBatchKey++;
		$this->currentBatchItemKey = 0;

		// Check the results
		$this->currentBatchItemCount = count($this->currentBatchItems);
		$this->retrievedItemCount += $this->currentBatchItemCount;

		if ($this->currentBatchItemCount === 0) {
			// We received nothing
			$this->isCurrentBatchValid = false;
			$this->moreBatchesMayExist = false;
		} else {
			// We did receive data, but is it full?
			$this->isCurrentBatchValid = true;
			$this->moreBatchesMayExist = ($this->currentBatchItemCount == $this->batchSize);
		}
	}

	/**
	 * Start over.
	 */
	public function rewind()
	{
		// Go back to first batch
		$this->currentBatchKey = 0;
		// Start optimistically
		$this->moreBatchesMayExist = true;
		// Nothing retrieved or processed so far
		$this->retrievedItemCount = 0;
		$this->processedItemCount = 0;

		// Fetch first batch
		$this->fetchNextBatch();
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