<?php
/**
 * コンテンツコメント 承認ボタン template
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * @param string $pluginKey プラグインキー
 * @param string $contentKey コンテントキー
 * @param array $contentComment コンテンツコメント一覧の1件データ
 * @param string $contentTitleForMail メールのためのコンテンツタイトル
 * @param bool $useCommentApproval コンテントコメント承認利用フラグ
 */
$this->NetCommonsHtml->css(array('/content_comments/css/style.css'));

?>
<?php /* 承認ボタン */ ?>
<?php echo $this->NetCommonsForm->create('ContentComment', array(
	'name' => 'form',
	'class' => 'content-comment-button',
	'url' => '/content_comments/content_comments/approve/' . Current::read('Frame.id'),
	'type' => 'put',
)); ?>
	<?php echo $this->NetCommonsForm->hidden('ContentComment.id', array('value' => $contentComment['ContentComment']['id'])); ?>
	<?php echo $this->NetCommonsForm->hidden('ContentComment.created_user', array('value' => $contentComment['ContentComment']['created_user'])); // 投稿者メール送信に必要 ?>
	<?php echo $this->NetCommonsForm->hidden('ContentComment.plugin_key', array('value' => $pluginKey)); ?>
	<?php echo $this->NetCommonsForm->hidden('ContentComment.content_key', array('value' => $contentKey)); ?>
	<?php echo $this->NetCommonsForm->hidden('ContentComment.status', array('value' => WorkflowComponent::STATUS_PUBLISHED)); //公開 ?>
	<?php echo $this->NetCommonsForm->hidden('ContentComment.comment', array('value' => $contentComment['ContentComment']['comment'])); ?>
	<?php echo $this->NetCommonsForm->hidden('Block.id', array('value' => Current::read('Block.id'))); ?>
	<?php echo $this->NetCommonsForm->hidden('_mail.content_title', array('value' => $contentTitleForMail)); ?>
	<?php echo $this->NetCommonsForm->hidden('_mail.use_comment_approval', array('value' => $useCommentApproval)); ?>

	<?php echo $this->NetCommonsForm->button(
		__d('content_comments', 'Approval'),
		array(
			'class' => 'btn btn-warning btn-xs',
			'onclick' => 'return confirm(\'' . sprintf(__d('content_comments', 'Approving the %s. Are you sure to proceed?'), __d('content_comments', 'comment')) . '\')',
			'ng-class' => '{disabled: sending}',
			'icon' => 'glyphicon-ok',
	)); ?>
<?php echo $this->NetCommonsForm->end();
