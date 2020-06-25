<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2018
 * @package Client
 * @subpackage Html
 */


namespace Aimeos\Client\Html\Email\Delivery;


/**
 * Default implementation of delivery emails.
 *
 * @package Client
 * @subpackage Html
 */
class Standard
	extends \Aimeos\Client\Html\Common\Client\Factory\Base
	implements \Aimeos\Client\Html\Common\Client\Factory\Iface
{
	/** client/html/email/delivery/standard/subparts
	 * List of HTML sub-clients rendered within the email delivery section
	 *
	 * The output of the frontend is composed of the code generated by the HTML
	 * clients. Each HTML client can consist of serveral (or none) sub-clients
	 * that are responsible for rendering certain sub-parts of the output. The
	 * sub-clients can contain HTML clients themselves and therefore a
	 * hierarchical tree of HTML clients is composed. Each HTML client creates
	 * the output that is placed inside the container of its parent.
	 *
	 * At first, always the HTML code generated by the parent is printed, then
	 * the HTML code of its sub-clients. The order of the HTML sub-clients
	 * determines the order of the output of these sub-clients inside the parent
	 * container. If the configured list of clients is
	 *
	 *  array( "subclient1", "subclient2" )
	 *
	 * you can easily change the order of the output by reordering the subparts:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1", "subclient2" )
	 *
	 * You can also remove one or more parts if they shouldn't be rendered:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1" )
	 *
	 * As the clients only generates structural HTML, the layout defined via CSS
	 * should support adding, removing or reordering content by a fluid like
	 * design.
	 *
	 * @param array List of sub-client names
	 * @since 2014.03
	 * @category Developer
	 */
	private $subPartPath = 'client/html/email/delivery/standard/subparts';

	/** client/html/email/delivery/text/name
	 * Name of the text part used by the email delivery client implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Client\Html\Email\Delivery\Text\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the client class name
	 * @since 2014.03
	 * @category Developer
	 */

	/** client/html/email/delivery/html/name
	 * Name of the html part used by the email delivery client implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Client\Html\Email\Delivery\Html\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the client class name
	 * @since 2014.03
	 * @category Developer
	 */
	private $subPartNames = array( 'text', 'html' );


	/**
	 * Returns the HTML code for insertion into the body.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @return string HTML code
	 */
	public function getBody( $uid = '' )
	{
		$view = $this->getObject()->addData( $this->getView() );

		$content = '';
		foreach( $this->getSubClients() as $subclient ) {
			$content .= $subclient->setView( $view )->getBody( $uid );
		}
		$view->deliveryBody = $content;


		/** client/html/email/delivery/attachments
		 * List of file paths whose content should be attached to all delivery e-mails
		 *
		 * This configuration option allows you to add files to the e-mails that are
		 * sent to the customer when the delivery status changes. These files can't be
		 * customer specific.
		 *
		 * @param array List of absolute file paths
		 * @since 2016.10
		 * @category Developer
		 * @category User
		 * @see client/html/email/payment/attachments
		 */
		$files = $view->config( 'client/html/email/delivery/attachments', [] );

		$this->addAttachments( $view->mail(), $files );


		/** client/html/email/delivery/standard/template-body
		 * Relative path to the text body template of the email delivery client.
		 *
		 * The template file contains the text and processing instructions
		 * to generate the result shown in the e-mail. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in client/html/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "standard" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "standard"
		 * should be replaced by the name of the new class.
		 *
		 * The email delivery text client allows to use a different template for
		 * each delivery status value. You can create a template for each delivery
		 * status and store it in the "email/delivery/<status number>/" directory
		 * below the "templates" directory (usually in client/html/templates). If no
		 * specific layout template is found, the common template in the
		 * "email/delivery/" directory is used.
		 *
		 * @param string Relative path to the template creating code for the e-mail body
		 * @since 2014.03
		 * @category Developer
		 * @see client/html/email/delivery/standard/template-header
		 */
		$tplconf = 'client/html/email/delivery/standard/template-body';

		$status = $view->extOrderItem->getDeliveryStatus();
		$default = array( 'email/delivery/' . $status . '/body-standard', 'email/delivery/body-standard' );

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Returns the HTML string for insertion into the header.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @return string|null String including HTML tags for the header on error
	 */
	public function getHeader( $uid = '' )
	{
		$view = $this->getObject()->addData( $this->getView() );

		$content = '';
		foreach( $this->getSubClients() as $subclient ) {
			$content .= $subclient->setView( $view )->getHeader( $uid );
		}
		$view->deliveryHeader = $content;


		$addr = $view->extAddressItem;

		$msg = $view->mail();
		$msg->addHeader( 'X-MailGenerator', 'Aimeos' );
		$msg->addTo( $addr->getEMail(), $addr->getFirstName() . ' ' . $addr->getLastName() );

		$addresses = $view->extOrderBaseItem->getAddress( \Aimeos\MShop\Order\Item\Base\Address\Base::TYPE_PAYMENT );

		if( ( $billAddr = current( $addresses ) ) !== false && $billAddr->getEMail() != $addr->getEmail() ) {
			$msg->addCc( $billAddr->getEMail(), $billAddr->getFirstName() . ' ' . $billAddr->getLastName() );
		}

		/** client/html/email/from-name
		 * Name used when sending e-mails
		 *
		 * The name of the person or e-mail account that is used for sending all
		 * shop related emails to customers.
		 *
		 * @param string Name shown in the e-mail
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/delivery/from-name
		 * @see client/html/email/from-email
		 * @see client/html/email/reply-email
		 * @see client/html/email/bcc-email
		 */
		$fromName = $view->config( 'client/html/email/from-name' );

		/** client/html/email/delivery/from-name
		 * Name used when sending delivery e-mails
		 *
		 * The name of the person or e-mail account that is used for sending all
		 * shop related delivery e-mails to customers. This configuration option
		 * overwrites the name set in "client/html/email/from-name".
		 *
		 * @param string Name shown in the e-mail
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/from-name
		 * @see client/html/email/from-email
		 * @see client/html/email/reply-email
		 * @see client/html/email/bcc-email
		 */
		$fromNameDelivery = $view->config( 'client/html/email/delivery/from-name', $fromName );

		/** client/html/email/from-email
		 * E-Mail address used when sending e-mails
		 *
		 * The e-mail address of the person or account that is used for sending
		 * all shop related emails to customers.
		 *
		 * @param string E-mail address
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/from-name
		 * @see client/html/email/delivery/from-email
		 * @see client/html/email/reply-email
		 * @see client/html/email/bcc-email
		 */
		$fromEmail = $view->config( 'client/html/email/from-email' );

		/** client/html/email/delivery/from-email
		 * E-Mail address used when sending delivery e-mails
		 *
		 * The e-mail address of the person or account that is used for sending
		 * all shop related delivery emails to customers. This configuration option
		 * overwrites the e-mail address set via "client/html/email/from-email".
		 *
		 * @param string E-mail address
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/delivery/from-name
		 * @see client/html/email/from-email
		 * @see client/html/email/reply-email
		 * @see client/html/email/bcc-email
		 */
		if( ( $fromEmailDelivery = $view->config( 'client/html/email/delivery/from-email', $fromEmail ) ) != null ) {
			$msg->addFrom( $fromEmailDelivery, $fromNameDelivery );
		}


		/** client/html/email/reply-name
		 * Recipient name displayed when the customer replies to e-mails
		 *
		 * The name of the person or e-mail account the customer should
		 * reply to in case of questions or problems. If no reply name is
		 * configured, the name person or e-mail account set via
		 * "client/html/email/from-name" is used.
		 *
		 * @param string Name shown in the e-mail
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/reply-email
		 * @see client/html/email/delivery/reply-email
		 * @see client/html/email/from-email
		 * @see client/html/email/from-name
		 * @see client/html/email/bcc-email
		 */
		$replyName = $view->config( 'client/html/email/reply-name', $fromName );

		/** client/html/email/delivery/reply-name
		 * Recipient name displayed when the customer replies to delivery e-mails
		 *
		 * The name of the person or e-mail account the customer should
		 * reply to in case of questions or problems. This configuration option
		 * overwrites the name set via "client/html/email/reply-name".
		 *
		 * @param string Name shown in the e-mail
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/delivery/reply-email
		 * @see client/html/email/reply-name
		 * @see client/html/email/reply-email
		 * @see client/html/email/from-email
		 * @see client/html/email/bcc-email
		 */
		$replyNameDelivery = $view->config( 'client/html/email/delivery/reply-name', $replyName );

		/** client/html/email/reply-email
		 * E-Mail address used by the customer when replying to e-mails
		 *
		 * The e-mail address of the person or e-mail account the customer
		 * should reply to in case of questions or problems.
		 *
		 * @param string E-mail address
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/reply-name
		 * @see client/html/email/delivery/reply-email
		 * @see client/html/email/from-email
		 * @see client/html/email/bcc-email
		 */
		$replyEmail = $view->config( 'client/html/email/reply-email', $fromEmail );

		/** client/html/email/delivery/reply-email
		 * E-Mail address used by the customer when replying to delivery e-mails
		 *
		 * The e-mail address of the person or e-mail account the customer
		 * should reply to in case of questions or problems. This configuration
		 * option overwrites the e-mail address set via "client/html/email/reply-email".
		 *
		 * @param string E-mail address
		 * @since 2014.03
		 * @category User
		 * @see client/html/email/delivery/reply-name
		 * @see client/html/email/reply-email
		 * @see client/html/email/from-email
		 * @see client/html/email/bcc-email
		 */
		if( ( $replyEmailDelivery = $view->config( 'client/html/email/delivery/reply-email', $replyEmail ) ) != null ) {
			$msg->addReplyTo( $replyEmailDelivery, $replyNameDelivery );
		}


		/** client/html/email/bcc-email
		 * E-Mail address all e-mails should be also sent to
		 *
		 * Using this option you can send a copy of all shop related e-mails to
		 * a second e-mail account. This can be handy for testing and checking
		 * the e-mails sent to customers.
		 *
		 * It also allows shop owners with a very small volume of orders to be
		 * notified about new orders. Be aware that this isn't useful if the
		 * order volumne is high or has peeks!
		 *
		 * @param string E-mail address
		 * @since 2014.03
		 * @category User
		 * @category Developer
		 * @see client/html/email/delivery/bcc-email
		 * @see client/html/email/reply-email
		 * @see client/html/email/from-email
		 */
		$bccEmail = $view->config( 'client/html/email/bcc-email' );

		/** client/html/email/delivery/bcc-email
		 * E-Mail address all delivery e-mails should be also sent to
		 *
		 * Using this option you can send a copy of all delivery related e-mails
		 * to a second e-mail account. This can be handy for testing and checking
		 * the e-mails sent to customers.
		 *
		 * It also allows shop owners with a very small volume of orders to be
		 * notified about new orders. Be aware that this isn't useful if the
		 * order volumne is high or has peeks!
		 *
		 * This configuration option overwrites the e-mail address set via
		 * "client/html/email/bcc-email".
		 *
		 * @param string|array E-mail address or list of e-mail addresses
		 * @since 2014.03
		 * @category User
		 * @category Developer
		 * @see client/html/email/bcc-email
		 * @see client/html/email/reply-email
		 * @see client/html/email/from-email
		 */
		if( ( $bccEmailDelivery = $view->config( 'client/html/email/delivery/bcc-email', $bccEmail ) ) != null )
		{
			foreach( (array) $bccEmailDelivery as $emailAddr ) {
				$msg->addBcc( $emailAddr );
			}
		}


		/** client/html/email/delivery/standard/template-header
		 * Relative path to the text header template of the email delivery client.
		 *
		 * The template file contains the text and processing instructions
		 * to generate the text that is inserted into the header
		 * of the e-mail. The configuration string is the
		 * path to the template file relative to the templates directory (usually
		 * in client/html/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "standard" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "standard"
		 * should be replaced by the name of the new class.
		 *
		 * The email payment text client allows to use a different template for
		 * each payment status value. You can create a template for each payment
		 * status and store it in the "email/payment/<status number>/" directory
		 * below the "templates" directory (usually in client/html/templates). If no
		 * specific layout template is found, the common template in the
		 * "email/payment/" directory is used.
		 *
		 * @param string Relative path to the template creating code for the e-mail header
		 * @since 2014.03
		 * @category Developer
		 * @see client/html/email/delivery/standard/template-body
		 */
		$tplconf = 'client/html/email/delivery/standard/template-header';

		$status = $view->extOrderItem->getDeliveryStatus();
		$default = array( 'email/delivery/' . $status . '/header-standard', 'email/delivery/header-standard' );

		return $view->render( $view->config( $tplconf, $default ) ); ;
	}


	/**
	 * Returns the sub-client given by its name.
	 *
	 * @param string $type Name of the client type
	 * @param string|null $name Name of the sub-client (Default if null)
	 * @return \Aimeos\Client\Html\Iface Sub-client object
	 */
	public function getSubClient( $type, $name = null )
	{
		/** client/html/email/delivery/decorators/excludes
		 * Excludes decorators added by the "common" option from the email delivery html client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "client/html/common/decorators/default" before they are wrapped
		 * around the html client.
		 *
		 *  client/html/email/delivery/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Client\Html\Common\Decorator\*") added via
		 * "client/html/common/decorators/default" to the html client.
		 *
		 * @param array List of decorator names
		 * @since 2014.05
		 * @category Developer
		 * @see client/html/common/decorators/default
		 * @see client/html/email/delivery/decorators/global
		 * @see client/html/email/delivery/decorators/local
		 */

		/** client/html/email/delivery/decorators/global
		 * Adds a list of globally available decorators only to the email delivery html client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\Client\Html\Common\Decorator\*") around the html client.
		 *
		 *  client/html/email/delivery/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Client\Html\Common\Decorator\Decorator1" only to the html client.
		 *
		 * @param array List of decorator names
		 * @since 2014.05
		 * @category Developer
		 * @see client/html/common/decorators/default
		 * @see client/html/email/delivery/decorators/excludes
		 * @see client/html/email/delivery/decorators/local
		 */

		/** client/html/email/delivery/decorators/local
		 * Adds a list of local decorators only to the email delivery html client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\Client\Html\Email\Decorator\*") around the html client.
		 *
		 *  client/html/email/delivery/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Client\Html\Email\Decorator\Decorator2" only to the html client.
		 *
		 * @param array List of decorator names
		 * @since 2014.05
		 * @category Developer
		 * @see client/html/common/decorators/default
		 * @see client/html/email/delivery/decorators/excludes
		 * @see client/html/email/delivery/decorators/global
		 */

		return $this->createSubClient( 'email/delivery/' . $type, $name );
	}


	/**
	 * Adds the given list of files as attachments to the mail message object
	 *
	 * @param \Aimeos\MW\Mail\Message\Iface $msg Mail message
	 * @param array $files List of absolute file paths
	 */
	protected function addAttachments( \Aimeos\MW\Mail\Message\Iface $msg, array $files )
	{
		foreach( $files as $filename )
		{
			if( ( $content = @file_get_contents( $filename ) ) === false ) {
				throw new \Aimeos\Client\Html\Exception( sprintf( 'File "%1$s" doesn\'t exist', $filename ) );
			}

			if( class_exists( 'finfo' ) )
			{
				try
				{
					$finfo = new \finfo( FILEINFO_MIME_TYPE );
					$mimetype = $finfo->file( $filename );
				}
				catch( \Exception $e )
				{
					throw new \Aimeos\Client\Html\Exception( $e->getMessage() );
				}
			}
			else if( function_exists( 'mime_content_type' ) )
			{
				$mimetype = mime_content_type( $filename );
			}
			else
			{
				$mimetype = 'application/binary';
			}

			$msg->addAttachment( $content, $mimetype, basename( $filename ) );
		}
	}


	/**
	 * Returns the list of sub-client names configured for the client.
	 *
	 * @return array List of HTML client names
	 */
	protected function getSubClientNames()
	{
		return $this->getContext()->getConfig()->get( $this->subPartPath, $this->subPartNames );
	}


	/**
	 * Sets the necessary parameter values in the view.
	 *
	 * @param \Aimeos\MW\View\Iface $view The view object which generates the HTML output
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return \Aimeos\MW\View\Iface Modified view object
	 */
	public function addData( \Aimeos\MW\View\Iface $view, array &$tags = [], &$expire = null )
	{
		$list = array(
			/// E-mail intro with first name (%1$s) and last name (%2$s)
			\Aimeos\MShop\Common\Item\Address\Base::SALUTATION_UNKNOWN => $view->translate( 'client', 'Dear %1$s %2$s' ),
			/// E-mail intro with first name (%1$s) and last name (%2$s)
			\Aimeos\MShop\Common\Item\Address\Base::SALUTATION_MR => $view->translate( 'client', 'Dear Mr %1$s %2$s' ),
			/// E-mail intro with first name (%1$s) and last name (%2$s)
			\Aimeos\MShop\Common\Item\Address\Base::SALUTATION_MRS => $view->translate( 'client', 'Dear Mrs %1$s %2$s' ),
			/// E-mail intro with first name (%1$s) and last name (%2$s)
			\Aimeos\MShop\Common\Item\Address\Base::SALUTATION_MISS => $view->translate( 'client', 'Dear Miss %1$s %2$s' ),
		);

		if( isset( $view->extAddressItem ) && ( $addr = $view->extAddressItem ) && isset( $list[$addr->getSalutation()] ) ) {
			$view->emailIntro = sprintf( $list[$addr->getSalutation()], $addr->getFirstName(), $addr->getLastName() );
		} else {
			$view->emailIntro = $view->translate( 'client', 'Dear Sir or Madam' );
		}

		return parent::addData( $view, $tags, $expire );
	}
}
