ContentComments
==============

[![Tests Status](https://github.com/NetCommons3/ContentComments/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/NetCommons3/ContentComments/actions/workflows/tests.yml)
[![Coverage Status](https://coveralls.io/repos/NetCommons3/ContentComments/badge.svg?branch=master)](https://coveralls.io/r/NetCommons3/ContentComments?branch=master)
[![Stable Version](https://img.shields.io/packagist/v/netcommons/content-comments.svg?label=stable)](https://packagist.org/packages/netcommons/content-comments)


### [phpdoc](https://netcommons3.github.io/NetCommons3Docs/phpdoc/ContentComments/)

### 概要
コンテンツの一覧にコメント数を表示する機能と、コンテンツの詳細でコメントを投稿する機能を提供します。<br>
利用するプラグインはコメントの使用有無(use_comment)、コメントの承認有無(use_comment_approval)を定義してください。<br>
<br>

#### コンテンツの一覧にコメント数を表示
ContentCommentBehaviorとContentCommentHelperを使用します。<br>
コメントと紐づくモデルにContentCommentBehavior、<br>
コンテンツ一覧のコントローラーにContentCommentHelperを定義してください。

##### サンプルコード
###### コントローラー
```php
class VideosController extends VideosAppController {

	public $uses = array(
		'Videos.Video',
		'Videos.VideoSetting'
	);

	public $helpers = array(
		'ContentComments.ContentComment' => array(
			'viewVarsKey' => array(
				'contentKey' => 'video.Video.key',
				'contentTitleForMail' => 'video.Video.title',
				'useComment' => 'videoSetting.use_comment',
				'useCommentApproval' => 'videoSetting.use_comment_approval'
			)
		)
	);

	public function index() {
		$query = array(
			'conditions' => array(
				'VideoSetting.block_key' => Current::read('Block.key')
			)
		);
		$viewVars['videoSetting'] = $this->VideoSetting->find('first', $query);
		$viewVars['videos'] = $this->Video->find('all');

		$this->set($viewVars);
	}
}
```

###### モデル
```php
class Video extends VideoAppModel {
	public $actsAs = array(
		'ContentComments.ContentComment'
	);
}
```

###### ビュー（ctpテンプレート）
```php
<?php
	foreach ($videos as $video) {
		echo $video['Video']['title'];
		echo $this->ContentComment->count($video);
	}
?>
```

<!--
##### [ContentCommentBehavior](https://github.com/NetCommons3/NetCommons3Docs/blob/master/phpdocMd/AuthorizationKeys/AuthorizationKeyComponent.md#authorizationkeycomponent)
##### [ContentCommentHelper](https://github.com/NetCommons3/NetCommons3Docs/blob/master/phpdocMd/AuthorizationKeys/AuthorizationKeyComponent.md#authorizationkeycomponent)
 -->
<br>

#### コンテンツの詳細でコメントを投稿する
ContentCommentsComponentとContentCommentHelperを使用します。<br>
コンテンツ詳細のコントローラーにContentCommentsComponentを定義してください。

##### サンプルコード
###### コントローラー
```php
class VideosController extends VideosAppController {

	public $uses = array(
		'Videos.Video',
		'Videos.VideoSetting'
	);

	public $components = array(
		'ContentComments.ContentComments' => array(
			'viewVarsKey' => array(
				'contentKey' => 'video.Video.key',
				'contentTitleForMail' => 'video.Video.title',
				'useComment' => 'videoSetting.use_comment'
				'useCommentApproval' => 'videoSetting.use_comment_approval'
			),
			'allow' => array('view')
		)
	)

	public function view($videoKey) {
		$query = array(
			'conditions' => array(
				'VideoSetting.block_key' => Current::read('Block.key')
			)
		);
		$viewVars['videoSetting'] = $this->VideoSetting->find('first', $query);

		$query = array(
			'conditions' => array(
				'Video.key' => $videoKey,
				'Video.language_id' => Current::read('Language.id')
			)
		);
		$viewVars['video'] = $this->Video->find('first', $query);

		$this->set($viewVars);
	}
}
```

###### ビュー（ctpテンプレート）
```
<?php
	echo $video['title'];
	echo $this->ContentComment->index($video);
?>
```

<!--
##### [ContentCommentsComponent](https://github.com/NetCommons3/NetCommons3Docs/blob/master/phpdocMd/AuthorizationKeys/AuthorizationKeyComponent.md#authorizationkeycomponent)
##### [ContentCommentHelper](https://github.com/NetCommons3/NetCommons3Docs/blob/master/phpdocMd/AuthorizationKeys/AuthorizationKeyComponent.md#authorizationkeycomponent)
 -->
