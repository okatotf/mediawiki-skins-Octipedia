<?php
/**
 * Nostalgia: A skin which looks like Wikipedia did in its first year (2001).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

use MediaWiki\MediaWikiServices;

/**
 * @todo document
 * @ingroup Skins
 */
class NostalgiaTemplate extends BaseTemplate {

	/**
	 * How many search boxes have we made?  Avoid duplicate id's.
	 * @var string|int
	 */
	protected $searchboxes = '';

	/** @var int */
	protected $mWatchLinkNum = 0;

	public function execute() {
		$this->html( 'headelement' );
		echo $this->beforeContent();
		$this->html( 'bodytext' );
		echo "\n";
		echo $this->afterContent();
		$this->html( 'dataAfterContent' );
		$this->printTrail();
		echo "\n</body></html>";
	}

	/**
	 * This will be called immediately after the "<body>" tag.
	 * @return string
	 */
	public function beforeContent() {
		$s = "\n<div id='content'>\n<div id='top'>\n";
		$s .= '<div id="logo">' . $this->getSkin()->logoText( 'right' ) . '</div>';

		$s .= $this->pageTitle();
		$s .= $this->pageSubtitle() . "\n";

		$s .= '<div id="topbar">';
		$s .= $this->topLinks() . "\n<br />";

		$notice = $this->getSkin()->getSiteNotice();
		if ( $notice ) {
			$s .= "\n<div id='siteNotice'>$notice</div>\n";
		}
		$s .= $this->pageTitleLinks();

		$ol = $this->otherLanguages();
		if ( $ol ) {
			$s .= '<br />' . $ol;
		}

		$s .= $this->getSkin()->getCategories();

		$s .= "<br clear='all' /></div><hr />\n</div>\n";
		$s .= "\n<div id='article'>";

		return $s;
	}

	/**
	 * This gets called shortly before the "</body>" tag.
	 * @return String HTML to be put before "</body>"
	 */
	public function afterContent() {
		$s = "\n</div><br clear='all' />\n";

		$s .= "\n<div id='footer'><hr />";

		$s .= $this->bottomLinks();
		$s .= "\n<br />" . $this->pageStats();
		$s .= "\n<br />" . $this->getSkin()->mainPageLink()
			. ' | ' . $this->getSkin()->aboutLink()
			. ' | ' . $this->searchForm();

		$s .= "\n</div>\n</div>\n";

		return $s;
	}

	/**
	 * @return string
	 */
	private function searchForm() {
		global $wgRequest, $wgUseTwoButtonsSearchForm;

		$search = $wgRequest->getText( 'search' );

		$s = '<form id="searchform' . $this->searchboxes
			. '" name="search" class="inline" method="post" action="'
			. htmlspecialchars( $this->data['searchaction'] ) . "\">\n"
			. '<input type="text" id="searchInput' . $this->searchboxes
			. '" name="search" size="19" value="'
			. htmlspecialchars( substr( $search, 0, 256 ) ) . "\" />\n"
			. '<input type="submit" name="go" value="' . wfMessage( 'searcharticle' )->escaped()
			. '" />';

		if ( $wgUseTwoButtonsSearchForm ) {
			$s .= '&#160;<input type="submit" name="fulltext" value="'
				. wfMessage( 'searchbutton' )->escaped() . "\" />\n";
		} else {
			$s .= ' <a href="' . htmlspecialchars( $this->data['searchaction'] ) . '" rel="search">'
				. wfMessage( 'powersearch-legend' )->escaped() . "</a>\n";
		}

		$s .= '</form>';

		// Ensure unique id's for search boxes made after the first
		$this->searchboxes = $this->searchboxes == '' ? 2 : $this->searchboxes + 1;

		return $s;
	}

	/**
	 * @return string
	 */
	private function pageStats() {
		$ret = [];
		$items = [ 'viewcount', 'credits', 'lastmod', 'numberofwatchingusers', 'copyright' ];

		foreach ( $items as $item ) {
			if ( $this->data[$item] !== false ) {
				$ret[] = $this->data[$item];
			}
		}

		return implode( ' ', $ret );
	}

	/**
	 * @return string
	 */
	private function topLinks() {
		$sep = " |\n";

		$s = $this->getSkin()->mainPageLink() . $sep
			. Linker::specialLink( 'Recentchanges' );

		if ( $this->data['isarticle'] ) {
			$s .= $sep . '<strong>' . $this->editThisPage() . '</strong>' . $sep . $this->talkLink()
				. $sep . $this->historyLink();
		}

		/* show links to different language variants */
		$s .= $this->variantLinks();
		if ( !$this->data['loggedin'] ) {
			$s .= $sep . Linker::specialLink( 'Userlogin' );
		} else {
			/* show user page and user talk links */
			$user = $this->getSkin()->getUser();
			$s .= $sep . Linker::link( $user->getUserPage(), wfMessage( 'mypage' )->escaped() );
			$s .= $sep . Linker::link( $user->getTalkPage(), wfMessage( 'mytalk' )->escaped() );
			if ( $user->getNewtalk() ) {
				$s .= ' *';
			}
			/* show watchlist link */
			$s .= $sep . Linker::specialLink( 'Watchlist' );
			/* show my contributions link */
			$s .= $sep . Linker::link(
				SpecialPage::getSafeTitleFor( 'Contributions', $this->data['username'] ),
				wfMessage( 'mycontris' )->escaped() );
			/* show my preferences link */
			$s .= $sep . Linker::specialLink( 'Preferences' );
			/* show upload file link */
			if ( UploadBase::isEnabled() && UploadBase::isAllowed( $user ) === true ) {
				$s .= $sep . $this->getUploadLink();
			}

			/* show log out link */
			$s .= $sep . Linker::specialLink( 'Userlogout' );
		}

		$s .= $sep . $this->specialPagesList();

		return $s;
	}

	/**
	 * Language/charset variant links for classic-style skins
	 * @return string
	 */
	private function variantLinks() {
		$s = '';

		/* show links to different language variants */
		global $wgDisableLangConversion, $wgLang;

		$title = $this->getSkin()->getTitle();
		$lang = $title->getPageLanguage();
		$variants = $lang->getVariants();

		if ( !$wgDisableLangConversion && count( $variants ) > 1
			&& !$title->isSpecialPage() ) {
			foreach ( $variants as $code ) {
				$varname = $lang->getVariantname( $code );

				if ( $varname == 'disable' ) {
					continue;
				}
				$s = $wgLang->pipeList( [
					$s,
					'<a href="' . htmlspecialchars( $title->getLocalURL( 'variant=' . $code ) )
						. '" lang="' . $code . '" hreflang="' . $code . '">'
						. htmlspecialchars( $varname ) . '</a>',
				] );
			}
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function bottomLinks() {
		$sep = wfMessage( 'pipe-separator' )->escaped() . "\n";
		$out = $this->getSkin()->getOutput();
		$user = $this->getSkin()->getUser();

		$s = '';
		if ( $out->isArticleRelated() ) {
			$element = [ '<strong>' . $this->editThisPage() . '</strong>' ];

			if ( $user->isLoggedIn() ) {
				$element[] = $this->watchThisPage();
			}

			$element[] = $this->talkLink();
			$element[] = $this->historyLink();
			$element[] = $this->whatLinksHere();
			$element[] = $this->watchPageLinksLink();

			$title = $this->getSkin()->getTitle();

			if (
				$title->getNamespace() == NS_USER ||
				$title->getNamespace() == NS_USER_TALK
			) {
				$id = User::idFromName( $title->getText() );
				$ip = User::isIP( $title->getText() );

				# Both anons and non-anons have contributions list
				if ( $id || $ip ) {
					$element[] = $this->userContribsLink();
				}

				if ( $id && $this->getSkin()->showEmailUser( $id ) ) {
					$element[] = $this->emailUserLink();
				}
			}

			$s = implode( $sep, $element );

			if ( $title->getArticleID() ) {
				$s .= "\n<br />";

				// Delete/protect/move links for privileged users
				if ( $user->isAllowed( 'delete' ) ) {
					$s .= $this->deleteThisPage();
				}

				if ( $user->isAllowed( 'protect' ) && $title->getRestrictionTypes() ) {
					$s .= $sep . $this->protectThisPage();
				}

				if ( $user->isAllowed( 'move' ) ) {
					$s .= $sep . $this->moveThisPage();
				}
			}

			$s .= "<br />\n" . $this->otherLanguages();
		}

		return $s;
	}

	/**
	 * @return string
	 * @throws MWException
	 */
	private function otherLanguages() {
		global $wgLang, $wgHideInterlanguageLinks;

		if ( $wgHideInterlanguageLinks ) {
			return '';
		}

		$a = $this->getSkin()->getOutput()->getLanguageLinks();

		if ( 0 == count( $a ) ) {
			return '';
		}

		$s = wfMessage( 'otherlanguages' )->escaped() . wfMessage( 'colon-separator' )->escaped();
		$first = true;

		if ( $wgLang->isRTL() ) {
			$s .= '<span dir="ltr">';
		}

		foreach ( $a as $l ) {
			if ( !$first ) {
				$s .= wfMessage( 'pipe-separator' )->escaped();
			}

			$first = false;

			$nt = Title::newFromText( $l );
			$text = Language::fetchLanguageName( $nt->getInterwiki() );

			$s .= Html::element( 'a',
				[ 'href' => $nt->getFullURL(), 'title' => $nt->getText(), 'class' => 'external' ],
				$text == '' ? $l : $text );
		}

		if ( $wgLang->isRTL() ) {
			$s .= '</span>';
		}

		return $s;
	}

	/**
	 * Show a drop-down box of special pages
	 * @return string
	 */
	private function specialPagesList() {
		global $wgScript;

		$select = new XmlSelect( 'title' );
		$factory = MediaWikiServices::getInstance()->getSpecialPageFactory();
		$pages = $factory->getUsablePages( $this->getSkin()->getUser() );
		array_unshift( $pages, $factory->getPage( 'SpecialPages' ) );
		/** @var SpecialPage[] $pages */
		foreach ( $pages as $obj ) {
			$select->addOption( $obj->getDescription(),
				$obj->getPageTitle()->getPrefixedDBkey() );
		}

		return Html::rawElement( 'form',
			[ 'id' => 'specialpages', 'method' => 'get', 'action' => $wgScript ],
			$select->getHTML() . Html::element(
				'input',
				[ 'type' => 'submit', 'value' => wfMessage( 'go' )->text() ]
			)
		);
	}

	/**
	 * @return string
	 */
	private function pageTitleLinks() {
		global $wgRequest, $wgLang;

		$oldid = $wgRequest->getVal( 'oldid' );
		$diff = $wgRequest->getVal( 'diff' );
		$action = $wgRequest->getText( 'action' );

		$skin = $this->getSkin();
		$title = $skin->getTitle();
		$out = $skin->getOutput();
		$user = $skin->getUser();

		$s = [ $this->printableLink() ];
		$disclaimer = $skin->disclaimerLink();

		# may be empty
		if ( $disclaimer ) {
			$s[] = $disclaimer;
		}

		$privacy = $skin->privacyLink();

		# may be empty too
		if ( $privacy ) {
			$s[] = $privacy;
		}

		if ( $out->isArticleRelated() ) {
			if ( $title->getNamespace() == NS_FILE ) {
				$image = wfFindFile( $title );

				if ( $image ) {
					$href = $image->getURL();
					$s[] = Html::element( 'a', [ 'href' => $href,
						'title' => $href ], $title->getText() );

				}
			}
		}

		if ( 'history' == $action || isset( $diff ) || isset( $oldid ) ) {
			$s[] .= Linker::linkKnown(
				$title,
				wfMessage( 'currentrev' )->escaped()
			);
		}

		if ( $user->getNewtalk() ) {
			# do not show "You have new messages" text when we are viewing our
			# own talk page
			if ( !$title->equals( $user->getTalkPage() ) ) {
				$tl = Linker::linkKnown(
					$user->getTalkPage(),
					wfMessage( 'nostalgia-newmessageslink' )->escaped(),
					[],
					[ 'redirect' => 'no' ]
				);

				$dl = Linker::linkKnown(
					$user->getTalkPage(),
					wfMessage( 'nostalgia-newmessagesdifflink' )->escaped(),
					[],
					[ 'diff' => 'cur' ]
				);
				$s[] = '<strong>' . wfMessage( 'youhavenewmessages' )
					->rawParams( $tl, $dl )->escaped() . '</strong>';
				# disable caching
				$out->setCdnMaxage( 0 );
				$out->enableClientCache( false );
			}
		}

		$undelete = $skin->getUndeleteLink();

		if ( !empty( $undelete ) ) {
			$s[] = $undelete;
		}

		return $wgLang->pipeList( $s );
	}

	/**
	 * Gets the h1 element with the page title.
	 * @return string
	 */
	private function pageTitle() {
		return '<h1 class="pagetitle">' .
			$this->getSkin()->getOutput()->getPageTitle() .
			'</h1>';
	}

	/**
	 * @return string
	 */
	private function pageSubtitle() {
		$sub = $this->getSkin()->getOutput()->getSubtitle();

		if ( $sub == '' ) {
			$sub = wfMessage( 'tagline' )->parse();
		}

		$subpages = $this->getSkin()->subPageSubtitle();
		$sub .= !empty( $subpages ) ? "</p><p class='subpages'>$subpages" : '';
		$s = "<p class='subtitle'>{$sub}</p>\n";

		return $s;
	}

	/**
	 * @return string
	 */
	private function printableLink() {
		global $wgRequest, $wgLang;
		$out = $this->getSkin()->getOutput();

		$s = [];

		if ( !$out->isPrintable() ) {
			$printurl = htmlspecialchars( $this->getSkin()->getTitle()->getLocalURL(
				$wgRequest->appendQueryValue( 'printable', 'yes', true ) ) );
			$s[] = "<a href=\"$printurl\" rel=\"alternate\">"
				. wfMessage( 'printableversion' )->escaped() . '</a>';
		}

		if ( $out->isSyndicated() ) {
			foreach ( $out->getSyndicationLinks() as $format => $link ) {
				$feedurl = htmlspecialchars( $link );
				$s[] = "<a href=\"$feedurl\" rel=\"alternate\" type=\"application/{$format}+xml\""
						. " class=\"feedlink\">" . wfMessage( "feed-$format" )->escaped() . "</a>";
			}
		}
		return $wgLang->pipeList( $s );
	}

	/**
	 * @return string
	 */
	private function editThisPage() {
		if ( !$this->getSkin()->getOutput()->isArticleRelated() ) {
			$s = wfMessage( 'protectedpage' )->escaped();
		} else {
			$title = $this->getSkin()->getTitle();
			$user = $this->getSkin()->getUser();
			$permManager = MediaWikiServices::getInstance()->getPermissionManager();
			if ( $permManager->quickUserCan( 'edit', $user, $title ) && $title->exists() ) {
				$t = wfMessage( 'nostalgia-editthispage' )->escaped();
			} elseif ( $permManager->quickUserCan( 'create', $user, $title ) && !$title->exists() ) {
				$t = wfMessage( 'nostalgia-create-this-page' )->escaped();
			} else {
				$t = wfMessage( 'viewsource' )->escaped();
			}

			$s = Linker::linkKnown(
				$title,
				$t,
				[],
				$this->getSkin()->editUrlOptions()
			);
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function deleteThisPage() {
		global $wgRequest;

		$diff = $wgRequest->getVal( 'diff' );
		$title = $this->getSkin()->getTitle();

		if ( $title->getArticleID() && ( !$diff ) &&
			$this->getSkin()->getUser()->isAllowed( 'delete' ) ) {
			$t = wfMessage( 'nostalgia-deletethispage' )->escaped();

			$s = Linker::linkKnown(
				$title,
				$t,
				[],
				[ 'action' => 'delete' ]
			);
		} else {
			$s = '';
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function protectThisPage() {
		global $wgRequest;

		$diff = $wgRequest->getVal( 'diff' );
		$title = $this->getSkin()->getTitle();

		if ( $title->getArticleID() && ( !$diff ) &&
			$this->getSkin()->getUser()->isAllowed( 'protect' ) &&
			$title->getRestrictionTypes()
		) {
			if ( $title->isProtected() ) {
				$text = wfMessage( 'nostalgia-unprotectthispage' )->escaped();
				$query = [ 'action' => 'unprotect' ];
			} else {
				$text = wfMessage( 'nostalgia-protectthispage' )->escaped();
				$query = [ 'action' => 'protect' ];
			}

			$s = Linker::linkKnown(
				$title,
				$text,
				[],
				$query
			);
		} else {
			$s = '';
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function watchThisPage() {
		++$this->mWatchLinkNum;

		// Cache
		$skin = $this->getSkin();
		$title = $skin->getTitle();

		if ( $skin->getOutput()->isArticleRelated() ) {
			if ( $skin->getUser()->isWatched( $title ) ) {
				$text = wfMessage( 'unwatchthispage' )->escaped();
				$query = [
					'action' => 'unwatch',
				];
				$id = 'mw-unwatch-link' . $this->mWatchLinkNum;
			} else {
				$text = wfMessage( 'watchthispage' )->escaped();
				$query = [
					'action' => 'watch',
				];
				$id = 'mw-watch-link' . $this->mWatchLinkNum;
			}

			$s = Linker::linkKnown(
				$title,
				$text,
				[
					'id' => $id,
					'class' => 'mw-watchlink',
				],
				$query
			);
		} else {
			$s = wfMessage( 'notanarticle' )->escaped();
		}

		return $s;
	}

	/**
	 * @return string
	 */
	private function moveThisPage() {
		$title = $this->getSkin()->getTitle();
		$permManager = MediaWikiServices::getInstance()->getPermissionManager();
		$user = $this->getSkin()->getUser();

		if ( $permManager->quickUserCan( 'move', $user, $title ) ) {
			return Linker::linkKnown(
				SpecialPage::getTitleFor( 'Movepage' ),
				wfMessage( 'movethispage' )->escaped(),
				[],
				[ 'target' => $title->getPrefixedDBkey() ]
			);
		} else {
			// no message if page is protected - would be redundant
			return '';
		}
	}

	/**
	 * @return string
	 */
	private function historyLink() {
		return Linker::link(
			$this->getSkin()->getTitle(),
			wfMessage( 'history' )->escaped(),
			[ 'rel' => 'archives' ],
			[ 'action' => 'history' ]
		);
	}

	/**
	 * @return string
	 */
	private function whatLinksHere() {
		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Whatlinkshere', $this->getSkin()->getTitle()->getPrefixedDBkey() ),
			wfMessage( 'whatlinkshere' )->escaped()
		);
	}

	/**
	 * @return string
	 */
	private function userContribsLink() {
		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Contributions', $this->getSkin()->getTitle()->getDBkey() ),
			wfMessage( 'contributions' )->escaped()
		);
	}

	/**
	 * @return string
	 */
	private function emailUserLink() {
		return Linker::linkKnown(
			SpecialPage::getTitleFor( 'Emailuser', $this->getSkin()->getTitle()->getDBkey() ),
			wfMessage( 'emailuser' )->escaped()
		);
	}

	/**
	 * @return string
	 */
	private function watchPageLinksLink() {
		if ( !$this->getSkin()->getOutput()->isArticleRelated() ) {
			return wfMessage( 'parentheses', wfMessage( 'notanarticle' )->text() )->escaped();
		} else {
			return Linker::linkKnown(
				SpecialPage::getTitleFor( 'Recentchangeslinked',
					$this->getSkin()->getTitle()->getPrefixedDBkey() ),
				wfMessage( 'recentchangeslinked-toolbox' )->escaped()
			);
		}
	}

	/**
	 * @return string
	 */
	private function talkLink() {
		$title = $this->getSkin()->getTitle();
		if ( NS_SPECIAL == $title->getNamespace() ) {
			# No discussion links for special pages
			return '';
		}

		$linkOptions = [];

		if ( $title->isTalkPage() ) {
			$link = $title->getSubjectPage();
			switch ( $link->getNamespace() ) {
				case NS_MAIN:
					$text = wfMessage( 'nostalgia-articlepage' );
					break;
				case NS_USER:
					$text = wfMessage( 'nostalgia-userpage' );
					break;
				case NS_PROJECT:
					$text = wfMessage( 'nostalgia-projectpage' );
					break;
				case NS_FILE:
					$text = wfMessage( 'imagepage' );
					# Make link known if image exists, even if the desc. page doesn't.
					if ( wfFindFile( $link ) ) {
						$linkOptions[] = 'known';
					}
					break;
				case NS_MEDIAWIKI:
					$text = wfMessage( 'mediawikipage' );
					break;
				case NS_TEMPLATE:
					$text = wfMessage( 'templatepage' );
					break;
				case NS_HELP:
					$text = wfMessage( 'viewhelppage' );
					break;
				case NS_CATEGORY:
					$text = wfMessage( 'categorypage' );
					break;
				default:
					$text = wfMessage( 'nostalgia-articlepage' );
			}
		} else {
			$link = $title->getTalkPage();
			$text = wfMessage( 'nostalgia-talkpage' );
		}

		$s = Linker::link( $link, $text->escaped(), [], [], $linkOptions );

		return $s;
	}

	/**
	 * @return string
	 */
	private function getUploadLink() {
		global $wgUploadNavigationUrl;

		if ( $wgUploadNavigationUrl ) {
			# Using an empty class attribute to avoid automatic setting of "external" class
			return Linker::makeExternalLink( $wgUploadNavigationUrl,
				wfMessage( 'upload' )->text(),
				true, '', [ 'class' => '' ] );
		} else {
			return Linker::linkKnown(
				SpecialPage::getTitleFor( 'Upload' ),
				wfMessage( 'upload' )->escaped()
			);
		}
	}
}
