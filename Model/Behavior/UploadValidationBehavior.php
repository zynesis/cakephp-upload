<?php

App::uses('Hash', 'Utility');

class UploadValidationBehavior extends AbstractUploadBehavior {

	public $defaults = array(
		'rootDir' => null,
		'path' => '{ROOT}webroot{DS}files{DS}{model}{DS}{field}{DS}',
		'mimetypes' => array(),
		'extensions' => array(),
		'maxSize' => 2097152,
		'minSize' => 8,
		'maxHeight' => 0,
		'minHeight' => 0,
		'maxWidth' => 0,
		'minWidth' => 0,
	);

/**
 * Setup a particular setting field
 *
 * @param Model $model Model instance
 * @param string $field Name of field being modified
 * @param array $options array of configuration settings for a field
 * @return void
 */
	protected function _setupField(Model $model, $field, $options) {
		if (is_int($field)) {
			$field = $options;
			$options = array();
		}

		$this->defaults['rootDir'] = ROOT . DS . APP_DIR . DS;
		if (!isset($this->settings[$model->alias][$field])) {
			$options = array_merge($this->defaults, (array)$options);

			if ($options['rootDir'] === null) {
				$options['rootDir'] = $this->defaults['rootDir'];
			}

			$options['path'] = Folder::slashTerm($this->_path($model, $field, array(
				'isThumbnail' => false,
				'path' => $options['path'],
				'rootDir' => $options['rootDir']
			)));

			$this->settings[$model->alias][$field] = $options;
		}
	}

/**
 * Check that the file does not exceed the max
 * file size specified by PHP
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @return boolean Success
 */
	public function isUnderPhpSizeLimit(Model $model, $check) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		return Hash::get($check[$field], 'error') !== UPLOAD_ERR_INI_SIZE;
	}

/**
 * Check that the file does not exceed the max
 * file size specified in the HTML Form
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @return boolean Success
 */
	public function isUnderFormSizeLimit(Model $model, $check) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		return Hash::get($check[$field], 'error') !== UPLOAD_ERR_FORM_SIZE;
	}

/**
 * Check that the file was completely uploaded
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @return boolean Success
 */
	public function isCompletedUpload(Model $model, $check) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		return Hash::get($check[$field], 'error') !== UPLOAD_ERR_PARTIAL;
	}

/**
 * Check that a file was uploaded
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @return boolean Success
 */
	public function isFileUpload(Model $model, $check) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		return Hash::get($check[$field], 'error') !== UPLOAD_ERR_NO_FILE;
	}

/**
 * Check that either a file was uploaded,
 * or the existing value in the database is not blank.
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @return boolean Success
 */
	public function isFileUploadOrHasExistingValue(Model $model, $check) {
		if (!$this->isFileUpload($model, $check)) {
			$pkey = $model->primaryKey;
			if (!empty($model->data[$model->alias][$pkey])) {
				$field = $this->_getField($check);
				$fieldValue = $model->field($field, array($pkey => $model->data[$model->alias][$pkey]));
				return !empty($fieldValue);
			}

			return false;
		}
		return true;
	}

/**
 * Check that the PHP temporary directory is missing
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function tempDirExists(Model $model, $check, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		return $error !== UPLOAD_ERR_NO_TMP_DIR;
	}

/**
 * Check that the file was successfully written to the server
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isSuccessfulWrite(Model $model, $check, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		return $error !== UPLOAD_ERR_CANT_WRITE;
	}

/**
 * Check that a PHP extension did not cause an error
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function noPhpExtensionErrors(Model $model, $check, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		return $error !== UPLOAD_ERR_EXTENSION;
	}

/**
 * Check that the file is of a valid mimetype
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param array $mimetypes file mimetypes to allow
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isValidMimeType(Model $model, $check, $mimetypes = array(), $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the mimetype is invalid
		if (!isset($check[$field]['type']) || !strlen($check[$field]['type'])) {
			return false;
		}

		// Sometimes the user passes in a string instead of an array
		if (is_string($mimetypes)) {
			$mimetypes = array($mimetypes);
		}

		foreach ($mimetypes as $key => $value) {
			if (!is_int($key)) {
				$mimetypes = $this->settings[$model->alias][$field]['mimetypes'];
				break;
			}
		}

		if (empty($mimetypes)) {
			$mimetypes = $this->settings[$model->alias][$field]['mimetypes'];
		}

		return in_array($check[$field]['type'], $mimetypes);
	}

/**
 * Check that the upload directory is writable
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isWritable(Model $model, $check, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		return is_writable($this->settings[$model->alias][$field]['path']);
	}

/**
 * Check that the upload directory exists
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isValidDir(Model $model, $check, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		return is_dir($this->settings[$model->alias][$field]['path']);
	}

/**
 * Check that the file is below the maximum file upload size
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param int $size Maximum file size
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isBelowMaxSize(Model $model, $check, $size = null, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the size is too small
		if (!isset($check[$field]['size']) || !strlen($check[$field]['size'])) {
			return false;
		}

		if (!$size) {
			$size = $this->settings[$model->alias][$field]['maxSize'];
		}

		return $check[$field]['size'] <= $size;
	}

/**
 * Check that the file is above the minimum file upload size
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param int $size Minimum file size
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isAboveMinSize(Model $model, $check, $size = null, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the size is too small
		if (!isset($check[$field]['size']) || !strlen($check[$field]['size'])) {
			return false;
		}

		if (!$size) {
			$size = $this->settings[$model->alias][$field]['minSize'];
		}

		return $check[$field]['size'] >= $size;
	}

/**
 * Check that the file has a valid extension
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param array $extensions file extenstions to allow
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isValidExtension(Model $model, $check, $extensions = array(), $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the extension is invalid
		if (!isset($check[$field]['name']) || !strlen($check[$field]['name'])) {
			return false;
		}

		// Sometimes the user passes in a string instead of an array
		if (is_string($extensions)) {
			$extensions = array($extensions);
		}

		// Sometimes a user does not specify any extensions in the validation rule
		foreach ($extensions as $key => $value) {
			if (!is_int($key)) {
				$extensions = $this->settings[$model->alias][$field]['extensions'];
				break;
			}
		}

		if (empty($extensions)) {
			$extensions = $this->settings[$model->alias][$field]['extensions'];
		}

		$pathInfo = $this->_pathinfo($check[$field]['name']);

		$extensions = array_map('strtolower', $extensions);
		return in_array(strtolower($pathInfo['extension']), $extensions);
	}

/**
 * Check that the file is above the minimum height requirement
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param int $height Height of Image
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isAboveMinHeight(Model $model, $check, $height = null, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the height is too big
		if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
			return false;
		}

		if (!$height) {
			$height = $this->settings[$model->alias][$field]['minHeight'];
		}

		list($imgWidth, $imgHeight) = getimagesize($check[$field]['tmp_name']);
		return $height > 0 && $imgHeight >= $height;
	}

/**
 * Check that the file is below the maximum height requirement
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param int $height Height of Image
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isBelowMaxHeight(Model $model, $check, $height = null, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the height is too big
		if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
			return false;
		}

		if (!$height) {
			$height = $this->settings[$model->alias][$field]['maxHeight'];
		}

		list($imgWidth, $imgHeight) = getimagesize($check[$field]['tmp_name']);
		return $height > 0 && $imgHeight <= $height;
	}

/**
 * Check that the file is above the minimum width requirement
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param int $width Width of Image
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isAboveMinWidth(Model $model, $check, $width = null, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the height is too big
		if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
			return false;
		}

		if (!$width) {
			$width = $this->settings[$model->alias][$field]['minWidth'];
		}

		list($imgWidth, $imgHeight) = getimagesize($check[$field]['tmp_name']);
		return $width > 0 && $imgWidth >= $width;
	}

/**
 * Check that the file is below the maximum width requirement
 *
 * @param Model $model Model instance
 * @param mixed $check Value to check
 * @param int $width Width of Image
 * @param boolean $requireUpload Whether or not to require a file upload
 * @return boolean Success
 */
	public function isBelowMaxWidth(Model $model, $check, $width = null, $requireUpload = true) {
		$field = $this->_getField($check);

		if (!empty($check[$field]['remove'])) {
			return true;
		}

		$error = Hash::get($check[$field], 'error');

		// Allow circumvention of this rule if uploads is not required
		if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
			return true;
		}

		// Non-file uploads also mean the height is too big
		if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
			return false;
		}

		if (!$width) {
			$width = $this->settings[$model->alias][$field]['maxWidth'];
		}

		list($imgWidth, $imgHeight) = getimagesize($check[$field]['tmp_name']);
		return $width > 0 && $imgWidth <= $width;
	}

/**
 * Returns the field to check
 *
 * @param array $check array of validation data
 * @return string
 **/
	protected function _getField($check) {
		$fieldKeys = array_keys($check);
		return array_pop($fieldKeys);
	}

}
