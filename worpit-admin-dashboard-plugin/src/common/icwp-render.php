<?php

class ICWP_APP_Render extends ICWP_APP_Foundation {

	/**
	 * @var \ICWP_APP_Render
	 */
	protected static $I = null;

	private function __construct() {
	}

	public static function GetInstance() :self {
		return self::$I ?? self::$I = new self();
	}

	protected $aRenderVars = [];

	/**
	 * @var string
	 */
	protected $sTemplatePath;

	/**
	 * @var string
	 */
	protected $sAutoloaderPath;

	/**
	 * @var string
	 */
	protected $sTemplate;

	public function render() :string {
		return $this->renderPhp();
	}

	private function renderPhp() :string {
		if ( \count( $this->getRenderVars() ) > 0 ) {
			\extract( $this->getRenderVars() );
		}

		$template = path_join( $this->getTemplateRoot(), $this->getTemplate() );
		if ( $this->loadFS()->isFile( $template ) ) {
			\ob_start();
			include( $template );
			$render = (string)\ob_get_contents();
			\ob_end_clean();
		}
		else {
			$render = 'Error: Template file not found: '.$template;
		}

		return $render;
	}

	public function display() {
		echo $this->render();
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplate() {
		$this->sTemplate = $this->loadDP()
								->addExtensionToFilePath( $this->sTemplate, $this->getEngineStub() );
		return $this->sTemplate;
	}

	/**
	 * @param string $sTemplate
	 * @return string
	 */
	public function getTemplateExists( $sTemplate = '' ) {
		$sFullPath = $this->getTemplateFullPath( $sTemplate );
		return $this->loadFS()->exists( $sFullPath );
	}

	/**
	 * @param string $template
	 * @return string
	 */
	public function getTemplateFullPath( $template = '' ) {
		if ( empty( $template ) ) {
			$template = $this->getTemplate();
		}
		$template = $this->loadDP()->addExtensionToFilePath( $template, $this->getEngineStub() );
		return path_join( $this->getTemplateRoot(), $template );
	}

	/**
	 * @return string
	 */
	public function getTemplateRoot() {
		$sPath = rtrim( $this->sTemplatePath, DIRECTORY_SEPARATOR );
		$sStub = $this->getEngineStub();
		if ( !preg_match( sprintf( '#%s$#', $sStub ), $sPath ) ) {
			$sPath = $sPath.DIRECTORY_SEPARATOR.$sStub;
		}
		return $sPath.DIRECTORY_SEPARATOR;
	}

	public function getRenderVars() :array {
		return $this->aRenderVars;
	}

	public function setRenderVars( array $vars ) :self {
		$this->aRenderVars = $vars;
		return $this;
	}

	/**
	 * @param string $sPath
	 * @return $this
	 */
	public function setAutoloaderPath( $sPath ) {
		$this->sAutoloaderPath = $sPath;
		return $this;
	}

	/**
	 * @param string $sPath
	 * @return $this
	 */
	public function setTemplate( $sPath ) {
		$this->sTemplate = $sPath;
		return $this;
	}

	/**
	 * @param string $sPath
	 * @return $this
	 */
	public function setTemplateRoot( $sPath ) {
		$this->sTemplatePath = $sPath;
		return $this;
	}

	private function getEngineStub() :string {
		return 'php';
	}
}