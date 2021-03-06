<?php
/**
 * Parsoid API wrapper.
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

class ApiVisualEditor extends ApiBase {

	/**
	 * @var Config
	 */
	protected $veConfig;

	/**
	 * @var VirtualRESTServiceClient
	 */
	protected $serviceClient;

	public function __construct( ApiMain $main, $name, Config $config ) {
		parent::__construct( $main, $name );
		$this->veConfig = $config;
		$forwardCookies = false;
		if ( $config->get( 'VisualEditorParsoidForwardCookies' ) && !User::isEveryoneAllowed( 'read' ) ) {
			$forwardCookies = RequestContext::getMain()->getRequest()->getHeader( 'Cookie' );
		}
		$this->serviceClient = new VirtualRESTServiceClient( new MultiHttpClient( array() ) );
		$this->serviceClient->mount( '/parsoid/', new ParsoidVirtualRESTService( array(
			'URL' => $config->get( 'VisualEditorParsoidURL' ),
			'prefix' => $config->get( 'VisualEditorParsoidPrefix' ),
			'timeout' => $config->get( 'VisualEditorParsoidTimeout' ),
			'HTTPProxy' => $config->get( 'VisualEditorParsoidHTTPProxy' ),
			'forwardCookies' => $forwardCookies,
		) ) );
	}

	private function requestParsoid( $method, $path, $params ) {
		$request = array(
			'method' => $method,
			'url' => '/parsoid/local/v1/' . $path
		);
		if ( $method === 'GET' ) {
			$request['query'] = $params;
		} else {
			$request['body'] = $params;
		}
		$response = $this->serviceClient->run( $request );
		if ( $response['code'] === 200 && $response['error'] === "" ) {
			// Pass thru performance data from Parsoid to the client, unless the response was
			// served directly from Varnish, in  which case discard the value of the XPP header
			// and use it to declare the cache hit instead.
			$xCache = $response['headers']['x-cache'];
			if ( is_string( $xCache ) && strpos( $xCache, 'hit' ) !== false ) {
				$xpp = 'cached-response=true';
			} else {
				$xpp = $response['headers']['x-parsoid-performance'];
			}
			if ( $xpp !== null ) {
				$resp = $this->getRequest()->response();
				$resp->header( 'X-Parsoid-Performance: ' . $xpp );
			}
		} elseif ( $response['error'] !== '' ) {
			$this->dieUsage( 'parsoidserver-http-error: ' . $response['error'], $response['error'] );
		} else { // error null, code not 200
			$this->dieUsage( 'parsoidserver-http: HTTP ' . $response['code'], $response['code'] );
		}
		return $response['body'];
	}

	protected function getHTML( $title, $parserParams ) {
		$restoring = false;

		if ( $title->exists() ) {
			$latestRevision = Revision::newFromTitle( $title );
			if ( $latestRevision === null ) {
				return false;
			}
			$revision = null;
			if ( !isset( $parserParams['oldid'] ) || $parserParams['oldid'] === 0 ) {
				$parserParams['oldid'] = $latestRevision->getId();
				$revision = $latestRevision;
			} else {
				$revision = Revision::newFromId( $parserParams['oldid'] );
				if ( $revision === null ) {
					return false;
				}
			}

			$restoring = $revision && !$revision->isCurrent();
			$oldid = $parserParams['oldid'];

			$content = $this->requestParsoid(
				'GET',
				'page/' . urlencode( $title->getPrefixedDBkey() ) . '/html',
				$parserParams
			);

			if ( $content === false ) {
				return false;
			}
			$timestamp = $latestRevision->getTimestamp();
		} else {
			$content = '';
			$timestamp = wfTimestampNow();
			$oldid = 0;
		}
		return array(
			'result' => array(
				'content' => $content,
				'basetimestamp' => $timestamp,
				'starttimestamp' => wfTimestampNow(),
				'oldid' => $oldid,
			),
			'restoring' => $restoring,
		);
	}

	protected function storeInSerializationCache( $title, $oldid, $html ) {
		global $wgMemc;
		$content = $this->postHTML( $title, $html, array( 'oldid' => $oldid ) );
		if ( $content === false ) {
			return false;
		}
		$hash = md5( $content );
		$key = wfMemcKey( 'visualeditor', 'serialization', $hash );
		$wgMemc->set( $key, $content, $this->veConfig->get( 'VisualEditorSerializationCacheTimeout' ) );
		return $hash;
	}

	protected function trySerializationCache( $hash ) {
		global $wgMemc;
		$key = wfMemcKey( 'visualeditor', 'serialization', $hash );
		return $wgMemc->get( $key );
	}

	protected function postHTML( $title, $html, $parserParams ) {
		if ( $parserParams['oldid'] === 0 ) {
			$parserParams['oldid'] = '';
		}
		return $this->requestParsoid(
			'POST',
			'transform/html/to/wikitext/' . urlencode( $title->getPrefixedDBkey() ),
			array(
				'html' => $html,
				'oldid' => $parserParams['oldid'],
			)
		);
	}

	protected function pstWikitext( $title, $wikitext ) {
		return ContentHandler::makeContent( $wikitext, $title )
			->preSaveTransform(
				$title,
				$this->getUser(),
				WikiPage::factory( $title )->makeParserOptions( $this->getContext() )
			)
			->serialize( 'text/x-wiki' );
	}

	protected function parseWikitextFragment( $title, $wikitext ) {
		return $this->requestParsoid(
			'POST',
			'transform/wikitext/to/html/' . urlencode( $title->getPrefixedDBkey() ),
			array(
				'wikitext' => $wikitext,
				'body' => 1,
			)
		);
	}

	protected function parseWikitext( $title ) {
		$apiParams = array(
			'action' => 'parse',
			'page' => $title->getPrefixedDBkey(),
			'prop' => 'text|revid|categorieshtml|displaytitle',
		);
		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(),
				$apiParams,
				false // was posted?
			),
			true // enable write?
		);

		$api->execute();
		if ( defined( 'ApiResult::META_CONTENT' ) ) {
			$result = $api->getResult()->getResultData();
			// Transform content nodes to '*'
			$result = ApiResult::transformForBC( $result );
			// Remove any metadata keys from the links array
			$result = ApiResult::removeMetadata( $result );
		} else {
			$result = $api->getResultData();
		}
		$content = isset( $result['parse']['text']['*'] ) ? $result['parse']['text']['*'] : false;
		$categorieshtml = isset( $result['parse']['categorieshtml']['*'] ) ?
			$result['parse']['categorieshtml']['*'] : false;
		$links = isset( $result['parse']['links'] ) ? $result['parse']['links'] : array();
		$revision = Revision::newFromId( $result['parse']['revid'] );
		$timestamp = $revision ? $revision->getTimestamp() : wfTimestampNow();
		$displaytitle = isset( $result['parse']['displaytitle'] ) ?
			$result['parse']['displaytitle'] : false;

		if ( $content === false || ( strlen( $content ) && $revision === null ) ) {
			return false;
		}

		if ( $displaytitle !== false ) {
			// Escape entities as in OutputPage::setPageTitle()
			$displaytitle = Sanitizer::normalizeCharReferences(
				Sanitizer::removeHTMLtags( $displaytitle ) );
		}

		return array(
			'content' => $content,
			'categorieshtml' => $categorieshtml,
			'basetimestamp' => $timestamp,
			'starttimestamp' => wfTimestampNow(),
			'displayTitleHtml' => $displaytitle
		);
	}

	protected function diffWikitext( $title, $wikitext ) {
		$apiParams = array(
			'action' => 'query',
			'prop' => 'revisions',
			'titles' => $title->getPrefixedDBkey(),
			'rvdifftotext' => $this->pstWikitext( $title, $wikitext )
		);
		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(),
				$apiParams,
				false // was posted?
			),
			false // enable write?
		);
		$api->execute();
		if ( defined( 'ApiResult::META_CONTENT' ) ) {
			$result = $api->getResult()->getResultData();
			// Transform content nodes to '*'
			$result = ApiResult::transformForBC( $result );
		} else {
			$result = $api->getResultData();
		}
		if ( !isset( $result['query']['pages'][$title->getArticleID()]['revisions'][0]['diff']['*'] ) ) {
			return array( 'result' => 'fail' );
		}
		$diffRows = $result['query']['pages'][$title->getArticleID()]['revisions'][0]['diff']['*'];

		if ( $diffRows !== '' ) {
			$context = new DerivativeContext( $this->getContext() );
			$context->setTitle( $title );
			$engine = new DifferenceEngine( $context );
			return array(
				'result' => 'success',
				'diff' => $engine->addHeader(
					$diffRows,
					$context->msg( 'currentrev' )->parse(),
					$context->msg( 'yourtext' )->parse()
				)
			);
		} else {
			return array( 'result' => 'nochanges' );
		}
	}

	protected function getLangLinks( $title ) {
		$apiParams = array(
			'action' => 'query',
			'prop' => 'langlinks',
			'lllimit' => 500,
			'titles' => $title->getPrefixedDBkey(),
			'indexpageids' => 1,
		);
		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(),
				$apiParams,
				false // was posted?
			),
			true // enable write?
		);

		$api->execute();
		if ( defined( 'ApiResult::META_CONTENT' ) ) {
			$result = $api->getResult()->getResultData();
			// Remove any metadata keys from the langlinks array
			$result = ApiResult::removeMetadata( $result );
		} else {
			$result = $api->getResultData();
		}
		if ( !isset( $result['query']['pages'][$title->getArticleID()]['langlinks'] ) ) {
			return false;
		}
		$langlinks = $result['query']['pages'][$title->getArticleID()]['langlinks'];
		$langnames = Language::fetchLanguageNames();
		foreach ( $langlinks as $i => $lang ) {
			$langlinks[$i]['langname'] = $langnames[$langlinks[$i]['lang']];
		}
		return $langlinks;
	}

	public function execute() {
		$user = $this->getUser();
		$params = $this->extractRequestParams();

		$page = Title::newFromText( $params['page'] );
		if ( !$page ) {
			$this->dieUsageMsg( 'invalidtitle', $params['page'] );
		}
		if ( !in_array( $page->getNamespace(), $this->veConfig->get( 'VisualEditorNamespaces' ) ) ) {
			$this->dieUsage( "VisualEditor is not enabled in namespace " .
				$page->getNamespace(), 'novenamespace' );
		}

		$parserParams = array();
		if ( isset( $params['oldid'] ) ) {
			$parserParams['oldid'] = $params['oldid'];
		}

		$html = $params['html'];
		if ( substr( $html, 0, 11 ) === 'rawdeflate,' ) {
			$html = gzinflate( base64_decode( substr( $html, 11 ) ) );
		}

		wfDebugLog( 'visualeditor', "called on '$page' with paction: '{$params['paction']}'" );
		switch ( $params['paction'] ) {
			case 'parse':
				$parsed = $this->getHTML( $page, $parserParams );
				// Dirty hack to provide the correct context for edit notices
				global $wgTitle; // FIXME NOOOOOOOOES
				$wgTitle = $page;
				RequestContext::getMain()->setTitle( $page );
				$notices = $page->getEditNotices();
				if ( $user->isAnon() ) {
					$notices[] = $this->msg(
						'anoneditwarning',
						// Log-in link
						'{{fullurl:Special:UserLogin|returnto={{FULLPAGENAMEE}}}}',
						// Sign-up link
						'{{fullurl:Special:UserLogin/signup|returnto={{FULLPAGENAMEE}}}}'
					)->parseAsBlock();
				}
				if ( $parsed && $parsed['restoring'] ) {
					$notices[] = $this->msg( 'editingold' )->parseAsBlock();
				}

				// Creating new page
				if ( !$page->exists() ) {
					$notices[] = $this->msg(
						$user->isLoggedIn() ? 'newarticletext' : 'newarticletextanon',
						Skin::makeInternalOrExternalUrl(
							$this->msg( 'helppage' )->inContentLanguage()->text()
						)
					)->parseAsBlock();
					// Page protected from creation
					if ( $page->getRestrictions( 'create' ) ) {
						$notices[] = $this->msg( 'titleprotectedwarning' )->parseAsBlock();
					}
				}

				// Look at protection status to set up notices + surface class(es)
				$protectedClasses = array();
				if ( MWNamespace::getRestrictionLevels( $page->getNamespace() ) !== array( '' ) ) {
					// Page protected from editing
					if ( $page->isProtected( 'edit' ) ) {
						# Is the title semi-protected?
						if ( $page->isSemiProtected() ) {
							$protectedClasses[] = 'mw-textarea-sprotected';

							$noticeMsg = 'semiprotectedpagewarning';
						} else {
							$protectedClasses[] = 'mw-textarea-protected';

							# Then it must be protected based on static groups (regular)
							$noticeMsg = 'protectedpagewarning';
						}
						$notices[] = $this->msg( $noticeMsg )->parseAsBlock() .
						$this->getLastLogEntry( $page, 'protect' );
					}

					// Deal with cascading edit protection
					list( $sources, $restrictions ) = $page->getCascadeProtectionSources();
					if ( isset( $restrictions['edit'] ) ) {
						$protectedClasses[] = ' mw-textarea-cprotected';

						$notice = $this->msg( 'cascadeprotectedwarning' )->parseAsBlock() . '<ul>';
						// Unfortunately there's no nice way to get only the pages which cause
						// editing to be restricted
						foreach ( $sources as $source ) {
							$notice .= "<li>" . Linker::link( $source ) . "</li>";
						}
						$notice .= '</ul>';
						$notices[] = $notice;
					}
				}

				if ( !$page->userCan( 'create' ) && !$page->exists() ) {
					$notices[] = $this->msg(
						'permissionserrorstext-withaction', 1, $this->msg( 'action-createpage' )
					) . "<br>" . $this->msg( 'nocreatetext' )->parse();
				}

				// Show notice when editing user / user talk page of a user that doesn't exist
				// or who is blocked
				// HACK of course this code is partly duplicated from EditPage.php :(
				if ( $page->getNamespace() == NS_USER || $page->getNamespace() == NS_USER_TALK ) {
					$parts = explode( '/', $page->getText(), 2 );
					$targetUsername = $parts[0];
					$targetUser = User::newFromName( $targetUsername, false /* allow IP users*/ );

					if (
						!( $targetUser && $targetUser->isLoggedIn() ) &&
						!User::isIP( $targetUsername )
					) { // User does not exist
						$notices[] = "<div class=\"mw-userpage-userdoesnotexist error\">\n" .
							$this->msg( 'userpage-userdoesnotexist', wfEscapeWikiText( $targetUsername ) ) .
							"\n</div>";
					} elseif ( $targetUser->isBlocked() ) { // Show log extract if the user is currently blocked
						$notices[] = $this->msg(
							'blocked-notice-logextract',
							$targetUser->getName() // Support GENDER in notice
						)->parseAsBlock() . $this->getLastLogEntry( $targetUser->getUserPage(), 'block' );
					}
				}

				if ( $user->isBlockedFrom( $page ) && $user->getBlock()->prevents( 'edit' ) !== false ) {
					$notices[] = call_user_func_array(
						array( $this, 'msg' ),
						$user->getBlock()->getPermissionsError( $this->getContext() )
					)->parseAsBlock();
				}

				if ( class_exists( 'GlobalBlocking' ) ) {
					$error = GlobalBlocking::getUserBlockErrors(
						$user,
						$this->getRequest()->getIP()
					);
					if ( count( $error ) ) {
						$notices[] = call_user_func_array(
							array( $this, 'msg' ),
							$error
						)->parseAsBlock();
					}
				}

				// HACK: Build a fake EditPage so we can get checkboxes from it
				$article = new Article( $page ); // Deliberately omitting ,0 so oldid comes from request
				$ep = new EditPage( $article );
				$req = $this->getRequest();
				$req->setVal( 'format', 'text/x-wiki' );
				$ep->importFormData( $req ); // By reference for some reason (bug 52466)
				$tabindex = 0;
				$states = array( 'minor' => false, 'watch' => false );
				$checkboxes = $ep->getCheckboxes( $tabindex, $states );

				// HACK: Find out which red links are on the page
				// We do the lookup for the current version. This might not be entirely complete
				// if we're loading an oldid, but it'll probably be close enough, and LinkCache
				// will automatically request any additional data it needs.
				$links = array();
				$wikipage = WikiPage::factory( $page );
				$popts = $wikipage->makeParserOptions( 'canonical' );
				$cached = ParserCache::singleton()->get( $article, $popts, true );
				$isOldRevision = isset( $params['oldid'] ) && $params['oldid'] != 0 &&
					$params['oldid'] != $page->getLatestRevID();
				$links = array(
					// Array of linked pages that are missing
					'missing' => array(),
					// For current revisions: true (treat all non-missing pages as existing)
					// For old revisions: array of linked pages that exist
					'extant' => $isOldRevision ? array() : true,
				);
				if ( $cached ) {
					foreach ( $cached->getLinks() as $ns => $dbks ) {
						foreach ( $dbks as $dbk => $id ) {
							$pft = Title::makeTitle( $ns, $dbk )->getPrefixedText();
							if ( $id == 0 ) {
								$links['missing'][] = $pft;
							} elseif ( $isOldRevision ) {
								$links['extant'][] = $pft;
							}
						}
					}
				}
				// Add information about current page
				if ( !$page->exists() ) {
					$links['missing'][] = $page->getPrefixedText();
				} elseif ( $isOldRevision ) {
					$links['extant'][] = $page->getPrefixedText();
				}

				// On parser cache miss, just don't bother populating red link data

				if ( $parsed === false ) {
					$this->dieUsage( 'Error contacting the Parsoid server', 'parsoidserver' );
				} else {
					$result = array_merge(
						array(
							'result' => 'success',
							'notices' => $notices,
							'checkboxes' => $checkboxes,
							'links' => $links,
							'protectedClasses' => implode( ' ', $protectedClasses ),
							'watched' => $user->isWatched( $page )
						),
						$parsed['result']
					);
				}
				break;

			case 'parsefragment':
				$wikitext = $params['wikitext'];
				if ( $params['pst'] ) {
					$wikitext = $this->pstWikitext( $page, $wikitext );
				}
				$content = $this->parseWikitextFragment( $page, $wikitext );
				if ( $content === false ) {
					$this->dieUsage( 'Error contacting the Parsoid server', 'parsoidserver' );
				} else {
					$result = array(
						'result' => 'success',
						'content' => $content
					);
				}
				break;

			case 'serialize':
				if ( $params['cachekey'] !== null ) {
					$content = $this->trySerializationCache( $params['cachekey'] );
					if ( !is_string( $content ) ) {
						$this->dieUsage( 'No cached serialization found with that key', 'badcachekey' );
					}
				} else {
					if ( $params['html'] === null ) {
						$this->dieUsageMsg( 'missingparam', 'html' );
					}
					$content = $this->postHTML( $page, $html, $parserParams );
					if ( $content === false ) {
						$this->dieUsage( 'Error contacting the Parsoid server', 'parsoidserver' );
					}
				}
				$result = array( 'result' => 'success', 'content' => $content );
				break;

			case 'diff':
				if ( $params['cachekey'] !== null ) {
					$wikitext = $this->trySerializationCache( $params['cachekey'] );
					if ( !is_string( $wikitext ) ) {
						$this->dieUsage( 'No cached serialization found with that key', 'badcachekey' );
					}
				} else {
					$wikitext = $this->postHTML( $page, $html, $parserParams );
					if ( $wikitext === false ) {
						$this->dieUsage( 'Error contacting the Parsoid server', 'parsoidserver' );
					}
				}

				$diff = $this->diffWikitext( $page, $wikitext );
				if ( $diff['result'] === 'fail' ) {
					$this->dieUsage( 'Diff failed', 'difffailed' );
				}
				$result = $diff;

				break;

			case 'serializeforcache':
				$key = $this->storeInSerializationCache( $page, $parserParams['oldid'], $html );
				$result = array( 'result' => 'success', 'cachekey' => $key );
				break;

			case 'getlanglinks':
				$langlinks = $this->getLangLinks( $page );
				if ( $langlinks === false ) {
					$this->dieUsage( 'Error querying MediaWiki API', 'parsoidserver' );
				} else {
					$result = array( 'result' => 'success', 'langlinks' => $langlinks );
				}
				break;
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * Gets the relevant HTML for the latest log entry on a given title, including a full log link.
	 *
	 * @param $title Title
	 * @param $types array|string
	 * @return string
	 */
	private function getLastLogEntry( $title, $types = '' ) {
		$lp = new LogPager(
			new LogEventsList( $this->getContext() ),
			$types,
			'',
			$title->getPrefixedDbKey()
		);
		$lp->mLimit = 1;

		return $lp->getBody() . Linker::link(
			SpecialPage::getTitleFor( 'Log' ),
			$this->msg( 'log-fulllog' )->escaped(),
			array(),
			array(
				'page' => $title->getPrefixedDBkey(),
				'type' => is_string( $types ) ? $types : null
			)
		);
	}

	public function getAllowedParams() {
		return array(
			'page' => array(
				ApiBase::PARAM_REQUIRED => true,
			),
			'format' => array(
				ApiBase::PARAM_DFLT => 'jsonfm',
				ApiBase::PARAM_TYPE => array( 'json', 'jsonfm' ),
			),
			'paction' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => array(
					'parse',
					'parsefragment',
					'serialize',
					'serializeforcache',
					'diff',
					'getlanglinks',
				),
			),
			'wikitext' => null,
			'basetimestamp' => null,
			'starttimestamp' => null,
			'oldid' => null,
			'html' => null,
			'cachekey' => null,
			'pst' => false,
		);
	}

	public function needsToken() {
		return false;
	}

	public function mustBePosted() {
		return false;
	}

	public function isInternal() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getParamDescription() {
		return array(
			'page' => 'The page to perform actions on.',
			'paction' => 'Action to perform',
			'oldid' => 'The revision number to use (defaults to latest version).',
			'html' => 'HTML to send to Parsoid to convert to wikitext',
			'wikitext' => 'Wikitext to send to Parsoid to convert to HTML (paction=parsefragment)',
			'pst' => 'Pre-save transform wikitext before sending it to Parsoid (paction=parsefragment)',
			'basetimestamp' => 'When saving, set this to the timestamp of the revision that was'
				. ' edited. Used to detect edit conflicts.',
			'starttimestamp' => 'When saving, set this to the timestamp of when the page was loaded.'
				. ' Used to detect edit conflicts.',
			'cachekey' => 'For serialize or diff, use the result of a previous serializeforcache'
				. ' request with this key. Overrides html.',
		);
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getDescription() {
		return 'Returns HTML5 for a page from the parsoid service.';
	}
}
