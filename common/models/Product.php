<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * This is the model class for table "{{%Product}}".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property float $price
 * @property int $status
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class Product extends \yii\db\ActiveRecord
{
	/**
	 * @var \yii\web\UploadedFile
	 */
	public $imageFile;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%products}}';
	}

	public function behaviors()
	{
		return [
			TimestampBehavior::class,
			BlameableBehavior::class
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['name', 'price', 'status',], 'required'],
			[['description'], 'string'],
			[['price'], 'number'],
			[['imageFile'], 'image', 'extensions' => 'png, jpg, jpeg, webp', 'maxSize' => 10 * 1024 * 1024],
			[['status', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],
			[['name'], 'string', 'max' => 255],
			[['image'], 'string', 'max' => 2000],
			[['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
			[['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['updated_by' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'name' => 'Name',
			'description' => 'Description',
			'imageFile' => 'Product Image',
			'price' => 'Price',
			'status' => 'Published',
			'created_at' => 'Create At',
			'updated_at' => 'Updated At',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
		];
	}

	/**
	 * {@inheritdoc}
	 * @return \common\models\query\ProductQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \common\models\query\ProductQuery(get_called_class());
	}

	public function save($runValidation = true, $attributeNames = null)
	{

		if ($this->imageFile) {
			$this->image = '/products/' . Yii::$app->security->generateRandomString() . '/' . $this->imageFile->name;
		}

		$transactions = Yii::$app->db->beginTransaction();
		$ok = parent::save($runValidation, $attributeNames);


		if ($ok && $this->imageFile) {
			$fullPath = Yii::getAlias('@frontend/web/storage' . $this->image);
			$dir = dirname($fullPath);
			if (!FileHelper::createDirectory($dir) | !$this->imageFile->saveAs($fullPath)) {
				$transactions->rollBack();
				return false;
			}

		}

		$transactions->commit();
		return $ok;
	}

	public function getImageUrl()
	{
		if ($this->image) {
		return Yii::$app->params['frontendUrl'] . '/storage' . $this->image;
		}

		return Yii::$app->params['frontendUrl'] . '/img/no_img.svg';
	}
}
