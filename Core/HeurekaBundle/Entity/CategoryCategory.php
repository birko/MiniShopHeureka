<?php

namespace Core\HeurekaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * CategoryCategory
 *
 * @ORM\Table(name="heureka_category_category")
 * @ORM\Entity(repositoryClass="Core\HeurekaBundle\Entity\CategoryCategoryRepository")
 */
class CategoryCategory
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
     * @ORM\ManyToOne(targetEntity="Core\CategoryBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
     protected $category;


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
     * Set category
     *
     * @param string $category
     * @return CategoryCategory
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
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
}
