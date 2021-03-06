<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 */


namespace Aimeos\Admin\JQAdm\Attribute\Price\Property;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->view = \TestHelperJqadm::getView();
		$this->context = \TestHelperJqadm::getContext();

		$this->object = new \Aimeos\Admin\JQAdm\Attribute\Price\Property\Standard( $this->context );
		$this->object = new \Aimeos\Admin\JQAdm\Common\Decorator\Page( $this->object, $this->context );
		$this->object->setAimeos( \TestHelperJqadm::getAimeos() );
		$this->object->setView( $this->view );
	}


	protected function tearDown()
	{
		unset( $this->object, $this->view, $this->context );
	}


	public function testCreate()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$this->view->item = $manager->createItem();
		$result = $this->object->create();

		$this->assertContains( 'Price properties', $result );
		$this->assertNull( $this->view->get( 'errors' ) );
	}


	public function testCopy()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$this->view->item = $manager->findItem( 'xs', ['price'], 'product', 'size' );
		$result = $this->object->copy();

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertContains( 'item-price-property', $result );
	}


	public function testDelete()
	{
		$result = $this->object->delete();

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertNull( $result );
	}


	public function testGet()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$this->view->item = $manager->findItem( 'xs', ['price'], 'product', 'size' );
		$result = $this->object->get();

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertContains( 'item-price-property', $result );
	}


	public function testSave()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$item = $manager->findItem( 'xs', ['price'], 'product', 'size' );
		$item->setCode( 'jqadm-test-price-property' );
		$item->setId( null );

		$this->view->item = $manager->saveItem( $item );

		$param = array(
			'site' => 'unittest',
			'price' => array(
				0 => array(
					'property' => array(
						0 => array(
							'price.property.id' => '',
							'price.property.type' => 'taxrate-local',
						),
					),
				),
			),
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $param );
		$this->view->addHelper( 'param', $helper );


		$result = $this->object->save();


		$manager->deleteItem( $this->view->item->getId() );

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertNull( $result );

		$priceItems = $this->view->item->getRefItems( 'price' );
		$this->assertEquals( 1, count( $priceItems ) );
		$this->assertEquals( 1, count( reset( $priceItems )->getPropertyItems() ) );
	}


	public function testSaveException()
	{
		$object = $this->getMockBuilder( \Aimeos\Admin\JQAdm\Attribute\Price\Property\Standard::class )
			->setConstructorArgs( array( $this->context, \TestHelperJqadm::getTemplatePaths() ) )
			->setMethods( array( 'fromArray' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'fromArray' )
			->will( $this->throwException( new \RuntimeException() ) );

		$this->view = \TestHelperJqadm::getView();
		$this->view->item = \Aimeos\MShop::create( $this->context, 'attribute' )->createItem();

		$object->setView( $this->view );

		$this->setExpectedException( \RuntimeException::class );
		$object->save();
	}


	public function testSearch()
	{
		$this->assertNull( $this->object->search() );
	}


	public function testGetSubClient()
	{
		$this->setExpectedException( \Aimeos\Admin\JQAdm\Exception::class );
		$this->object->getSubClient( 'unknown' );
	}
}
