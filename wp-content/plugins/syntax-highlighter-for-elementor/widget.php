<?php
namespace Solid_Syntax;

use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Highlighter_Widget extends Widget_Base {

	public static $slug = 'elementor-syntax-highlighter';
	private static $included = false;

	public function get_name() { return self::$slug; }

	public function get_title() { return __('Highlighted Code', self::$slug); }

	public function get_icon() { return 'fa fa-code'; }

	public function get_categories() { return [ 'basic' ]; }

	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Code', self::$slug ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'code_language',
			[
				'label' => __( 'Language', self::$slug ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'solid',
				'options' => [
					'bash' => __( 'bash', self::$slug ),
					'c' => __( 'c', self::$slug ),
					'clike' => __( 'clike', self::$slug ),
					'cpp' => __( 'cpp', self::$slug ),
					'csharp' => __( 'csharp', self::$slug ),
					'css' => __( 'css', self::$slug ),
					'fsharp' => __( 'fsharp', self::$slug ),
					'git' => __( 'git', self::$slug ),
					'go' => __( 'go', self::$slug ),
					'graphql' => __( 'graphql', self::$slug ),
					'haskell' => __( 'haskell', self::$slug ),
					'http' => __( 'http', self::$slug ),
					'java' => __( 'java', self::$slug ),
					'javadoc' => __( 'javadoc', self::$slug ),
					'javadoclike' => __( 'javadoclike', self::$slug ),
					'javascript' => __( 'javascript', self::$slug ),
					'jsdoc' => __( 'jsdoc', self::$slug ),
					'js-extras' => __( 'extras', self::$slug ),
					'js-templates' => __( 'templates', self::$slug ),
					'json' => __( 'json', self::$slug ),
					'jsx' => __( 'jsx', self::$slug ),
					'latex' => __( 'latex', self::$slug ),
					'lisp' => __( 'lisp', self::$slug ),
					'makefile' => __( 'makefile', self::$slug ),
					'markdown' => __( 'markdown', self::$slug ),
					'markup' => __( 'markup', self::$slug ),
					'markup-templating' => __( 'templating', self::$slug ),
					'matlab' => __( 'matlab', self::$slug ),
					'nginx' => __( 'nginx', self::$slug ),
					'objectivec' => __( 'objectivec', self::$slug ),
					'perl' => __( 'perl', self::$slug ),
					'php' => __( 'php', self::$slug ),
					'phpdoc' => __( 'phpdoc', self::$slug ),
					'php-extras' => __( 'extras', self::$slug ),
					'pug' => __( 'pug', self::$slug ),
					'python' => __( 'python', self::$slug ),
					'r' => __( 'r', self::$slug ),
					'tsx' => __( 'tsx', self::$slug ),
					'regex' => __( 'regex', self::$slug ),
					'sass' => __( 'sass', self::$slug ),
					'scss' => __( 'scss', self::$slug ),
					'scala' => __( 'scala', self::$slug ),
					'shell-session' => __( 'session', self::$slug ),
					'sql' => __( 'sql', self::$slug ),
					'twig' => __( 'twig', self::$slug ),
					'typescript' => __( 'typescript', self::$slug ),
				],
			]
		);

		$this->add_control(
			'code_block',
			[
				'label' => __( 'Code', self::$slug ),
				'type' => \Elementor\Controls_Manager::CODE,
				'language' => 'text',
				'rows' => 20,
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$content = $this->get_settings_for_display();

		$language = $content['code_language'];
		$code = htmlentities($content['code_block']);

		echo "<pre><code class='language-$language'>$code </code></pre>";
		echo $this->get_script_tag();
	}

	// This script is needed for the Elementor preview panel - a simple enqueue script won't work
	private function get_script_tag() {
		$url = plugins_url( 'assets/prism2.js', __FILE__ );

		return <<<EOT
<script>
if (!document.getElementById('syntaxed-prism')) {
	var my_awesome_script = document.createElement('script');
	my_awesome_script.setAttribute('src','$url');
	my_awesome_script.setAttribute('id','syntaxed-prism');
	document.body.appendChild(my_awesome_script);
} else {
	window.Prism && Prism.highlightAll();
}
</script>
EOT;
	}
}