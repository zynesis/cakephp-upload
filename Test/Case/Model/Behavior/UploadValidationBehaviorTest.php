<?php
App::uses('UploadValidationBehavior', 'Upload.Model/Behavior');

class TestUploadValidation extends CakeTestModel {

	public $useTable = 'uploads';

	public $actsAs = array(
		'Upload.Upload' => array(
			'photo' => array(
				'thumbnailMethod' => '_bad_thumbnail_method_',
				'pathMethod' => '_bad_path_method_',
			)
		)
	);

}
class UploadBehaviorTest extends CakeTestCase {

	public $fixtures = array('plugin.upload.upload');

	public $TestUpload = null;

/**
 * Called when a test case method has been executed
 *
 * @param string $method Test method that was executed.
 * @return void
 */
	public function endTest($method) {
		Classregistry::flush();
		unset($this->TestUpload);
	}

/**
 * Start Test callback
 *
 * @param string $method Test method that is about to be executed
 * @return void
 */
	public function startTest($method) {
		$this->TestUpload = ClassRegistry::init('TestUpload');

		$this->data['test_ok'] = array(
			'photo' => array(
				'name' => 'Photo.png',
				'tmp_name' => 'Photo.png',
				'dir' => '/tmp/php/file.tmp',
				'type' => 'image/png',
				'size' => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		);

		$this->data['test_ok_bmp'] = array(
			'photo' => array(
				'name' => 'Photo.bmp',
				'tmp_name' => 'Photo.bmp',
				'dir' => '/tmp/php/file.tmp',
				'type' => 'image/bmp',
				'size' => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		);

		$this->data['test_remove'] = array(
			'photo' => array(
				'remove' => true,
			)
		);
	}

	public function providerTestChangeConfigurationValidationRules() {
		return array(
			array('isWritable'),
			array('isValidDir'),
		);
	}

	public function providerTestValidationRules() {
		return array(
			array('isUnderPhpSizeLimit', array('error' => UPLOAD_ERR_INI_SIZE)),
			array('isUnderFormSizeLimit', array('error' => UPLOAD_ERR_FORM_SIZE)),
			array('isCompletedUpload', array('error' => UPLOAD_ERR_PARTIAL)),
			array('isFileUpload', array('error' => UPLOAD_ERR_NO_FILE)),
			array('isFileUploadOrHasExistingValue', array('error' => UPLOAD_ERR_NO_FILE), array('id' => 2)),
			array('isFileUploadOrHasExistingValue', array('error' => UPLOAD_ERR_NO_FILE), array('id' => '')),
			array('isFileUploadOrHasExistingValue', array('error' => UPLOAD_ERR_NO_FILE)),
		);
	}

	public function providerTestIgnorableValidationRules() {
		return array(
			array('tempDirExists', array('error' => UPLOAD_ERR_NO_TMP_DIR)),
			array('isSuccessfulWrite', array('error' => UPLOAD_ERR_CANT_WRITE)),
			array('noPhpExtensionErrors', array('error' => UPLOAD_ERR_EXTENSION)),
		);
	}

/**
 * Test changing configuration when running validation rules
 *
 * @param string $rule rule to test
 * @return void
 * @dataProvider providerTestChangeConfigurationValidationRules
 **/
	public function testChangeConfigurationValidationRules($rule) {
		$this->TestUpload->validate = array(
			'photo' => array(
				$rule => array(
					'rule' => $rule,
					'message' => $rule
				),
			)
		);

		$this->__testInvalidValidationRule($rule, $this->data['test_ok']);

		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'path' => TMP
			)
		));

		$this->__testOkCaseValidates();
		$this->__testRemoveCaseValidates();
	}

/**
 * Test validation rules
 *
 * @param string $rule rule to test
 * @param array $data validation data
 * @param array $record record to test
 * @return void
 * @dataProvider providerTestValidationRules
 **/
	public function testValidationRules($rule, $data, $record = array()) {
		$this->TestUpload->validate = array(
			'photo' => array(
				$rule => array(
					'rule' => $rule,
					'message' => $rule
				),
			)
		);

		$data = array_merge(array(
			'photo' => array_merge(array(
				'tmp_name' => 'Photo.png',
				'dir' => '/tmp/php/file.tmp',
				'type' => 'image/png',
				'size' => 8192,
			), $data
		), $record));

		$this->__testInvalidValidationRule($rule, $data);
		$this->__testOkAndRemoveCasesValidate();
	}

/**
 * Test ignorable validation rules
 *
 * @param string $rule rule to test
 * @param array $data validation data
 * @param array $record record to test
 * @return void
 * @dataProvider providerTestIgnorableValidationRules
 **/
	public function testIgnorableValidationRules($rule, $data, $record = array()) {
		$this->TestUpload->validate = array(
			'photo' => array(
				$rule => array(
					'rule' => $rule,
					'message' => $rule
				),
			)
		);

		$data = array_merge(array(
			'photo' => array_merge(array(
				'tmp_name' => 'Photo.png',
				'dir' => '/tmp/php/file.tmp',
				'type' => 'image/png',
				'size' => 8192,
			), $data
		), $record));
		$this->__testInvalidValidationRule($rule, $data);

		$this->TestUpload->validate = array('photo' => array(
			$rule => array('rule' => array($rule, false),
		)));
		$data = array('photo' => array('error' => UPLOAD_ERR_NO_FILE));
		$this->__testSkipValidationRule($rule, $data);
		$this->__testOkAndRemoveCasesValidate();
	}

/**
 * This simulates the case where we are uploading no file
 * to an existing record, which DOES have an existing value.
 *
 * @return void
 **/
	public function testIsFileUploadOrHasExistingValueEditingWithExistingValue() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isFileUploadOrHasExistingValue' => array(
					'rule' => 'isFileUploadOrHasExistingValue',
					'message' => 'isFileUploadOrHasExistingValue'
				),
			)
		);

		// Fixture record #1 has an existing value.
		$data = array(
			'id' => 1,
			'photo' => array(
				'tmp_name' => 'Photo.png',
				'dir' => '/tmp/php/file.tmp',
				'type' => 'image/png',
				'size' => 8192,
				'error' => UPLOAD_ERR_NO_FILE,
			)
		);
		$this->TestUpload->set($data);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEquals(0, count($this->TestUpload->validationErrors));

		$this->__testOkAndRemoveCasesValidate();
	}

	public function testIsValidMimeType() {
		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'mimetypes' => array('image/bmp', 'image/jpeg')
			)
		));

		$this->TestUpload->validate = array(
			'photo' => array(
				'isValidMimeType' => array(
					'rule' => 'isValidMimeType',
					'message' => 'isValidMimeType'
				),
			)
		);

		$this->__testInvalidValidationRule('isValidMimeType', $this->data['test_ok']);

		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'mimetypes' => array('image/png', 'image/jpeg')
			)
		));

		$this->__testOkAndRemoveCasesValidate();

		$this->TestUpload->validate = array(
			'photo' => array(
				'isValidMimeType' => array(
					'rule' => array('isValidMimeType', 'image/png'),
					'message' => 'isValidMimeType',
				),
			)
		);

		$this->__testOkCaseValidates();

		$this->TestUpload->validate = array('photo' => array(
			'isValidMimeType' => array('rule' => array('isValidMimeType', 'image/png', false),
		)));
		$data = array('photo' => array('error' => UPLOAD_ERR_NO_FILE));
		$this->__testSkipValidationRule('isValidMimeType', $data);
	}

	public function testIsValidExtension() {
		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'extensions' => array('jpeg', 'bmp')
			)
		));

		$this->TestUpload->validate = array(
			'photo' => array(
				'isValidExtension' => array(
					'rule' => 'isValidExtension',
					'message' => 'isValidExtension'
				),
			)
		);

		$this->__testInvalidValidationRule('isValidExtension', $this->data['test_ok']);

		$this->TestUpload->set($this->data['test_ok_bmp']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEquals(0, count($this->TestUpload->validationErrors));

		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo'
		));

		$this->TestUpload->validate['photo']['isValidExtension']['rule'] = array('isValidExtension', 'jpg');
		$this->__testInvalidValidationRule('isValidExtension', $this->data['test_ok']);

		$this->TestUpload->validate['photo']['isValidExtension']['rule'] = array('isValidExtension', array('jpg'));
		$this->__testInvalidValidationRule('isValidExtension', $this->data['test_ok']);

		$this->TestUpload->validate['photo']['isValidExtension']['rule'] = array('isValidExtension', array('jpg', 'bmp'));
		$this->__testInvalidValidationRule('isValidExtension', $this->data['test_ok']);

		$this->TestUpload->validate['photo']['isValidExtension']['rule'] = array('isValidExtension', array('jpg', 'bmp', 'png'));
		$this->__testOkCaseValidates();

		$this->TestUpload->validate = array(
			'photo' => array(
				'isFileUpload' => array(
					'rule' => 'isFileUpload',
					'message' => 'isFileUpload'
				),
				'isValidExtension' => array(
					'rule' => array('isValidExtension', array('jpg')),
					'message' => 'isValidExtension'
				),
			)
		);

		$this->__testInvalidValidationRule('isValidExtension', $this->data['test_ok']);

		$data = $this->data['test_ok'];
		$data['photo']['name'] = 'Photo.jpeg';
		$this->__testInvalidValidationRule('isValidExtension', $data);

		$this->__testRemoveCaseValidates();

		$this->TestUpload->validate = array('photo' => array(
			'isValidExtension' => array('rule' => array('isValidExtension', array('jpeg', 'bmp'), false),
		)));
		$data = array('photo' => array('error' => UPLOAD_ERR_NO_FILE));
		$this->__testSkipValidationRule('isValidExtension', $data);
	}

	private function __testOkAndRemoveCasesValidate() {
		$this->__testOkCaseValidates();
		$this->__testRemoveCaseValidates();
	}

	private function __testOkCaseValidates() {
		$this->__testValidates($this->data['test_ok']);
	}

	private function __testValidates($data) {
		$this->TestUpload->set($data);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEquals(0, count($this->TestUpload->validationErrors));
	}

	private function __testRemoveCaseValidates() {
		$this->TestUpload->set($this->data['test_remove']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEquals(0, count($this->TestUpload->validationErrors));
	}

	private function __testSkipValidationRule($ruleName, $data) {
		$this->TestUpload->set($data);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEquals(0, count($this->TestUpload->validationErrors));
	}

	private function __testInvalidValidationRule($ruleName, $data) {
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEquals(1, count($this->TestUpload->validationErrors));
		$this->assertEquals($ruleName, current($this->TestUpload->validationErrors['photo']));
	}

}
