<?php

namespace Core\HeurekaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * ProductCategory
 *
 * @ORM\Table(name="heureka_product_category")
 * @ORM\Entity(repositoryClass="Core\HeurekaBundle\Entity\ProductCategoryRepository")
 */
class ProductCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="heureka_category", type="integer")
     */
    private $heurekaCategory;
    
    /**
     * @ORM\ManyToOne(targetEntity="Core\ProductBundle\Entity\Product", inversedBy="prices")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $product;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set heurekaCategory
     *
     * @param string $heurekaCategory
     * @return CategoryCategory
     */
    public function setHeurekaCategory($heurekaCategory)
    {
        $this->heurekaCategory = $heurekaCategory;

        return $this;
    }

    /**
     * Get heurekaCategory
     *
     * @return int 
     */
    public function getHeurekaCategory()
    {
        return $this->heurekaCategory;
    }
    
    /**
     * Set product
     *
     * @param Product $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
