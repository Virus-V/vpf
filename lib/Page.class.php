<?php
/**
 *--------------------------------------------------
 * VPF 分页类
 *--------------------------------------------------
 * @program     : VPF
 * @create      : 2012-3-25 20:44:35
 * @description :
 */
namespace Vpf\Lib;
use Vpf;

class Page
{
    protected $pageSize = 20; /* 每页显示行数 */
    protected $nearSize = 3; /* 分页栏每页显示的页数 */
    protected $pageUrl = ''; /* 每页显示行数 */
    protected $totalPages; /* 分页总页面数 */
    protected $totalRows; /* 总行数 */
    protected $currentPage; /* 当前页 */
    /* 分页显示定制 */
    protected $config = array(
        'theme'=> '%firstPage%%prevPage%%currentPage%%nextPage%%lastPage%%total%'
    );

    /** 架构函数  */
    public function __construct($totalRows, $pageSize = '', $pageUrl = '', $nearSize = '')
    {
        $this->config['prev'] = L('PREV_PAGE');
        $this->config['next'] = L('NEXT_PAGE');
        $this->config['first'] = L('FIRST_PAGE');
        $this->config['last'] = L('LAST_PAGE');
        $this->totalRows = $totalRows;
        if(!empty($pageSize))
        {
            $this->pageSize = intval($pageSize);
        }
        if(!empty($nearSize))
        {
            $this->nearSize = intval($nearSize);
        }
        if(!empty($pageUrl))
        {
            $this->pageUrl = $pageUrl;
        }
        $this->totalPages = ceil($this->totalRows / $this->pageSize);
        $this->currentPage = (intval($_GET[C('VAR_PAGE')]) > 0) ? ((intval($_GET[C('VAR_PAGE')]) <= $this->totalPages) ? intval($_GET[C('VAR_PAGE')]) : $this->totalPages) : 1;
    }

    /** 配置 */
    public function set_config($name, $value)
    {
        if(isset($this->config[$name]))
        {
            $this->config[$name] = $value;
        }
    }

    /** 自动变量获取 */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    /** 分页显示输出 */
    public function show()
    {
        if(0 == $this->totalRows)
        {
            return '';
        }
        /* 首页 末页 */
        if(1 == $this->currentPage)
        {
            $firstPage = '<span class="firstPage">'.$this->config['first'].'</span>';
        }
        else
        {
            $firstPage = '<a class="firstPage" href="'.$this->get_pageUrl(1).'">'.$this->config['first'].'</a>';
        }
        if($this->totalPages == $this->currentPage)
        {
            $lastPage = '<span class="lastPage">'.$this->config['last'].'</span>';
        }
        else
        {
            $lastPage = '<a class="lastPage" href="'.$this->get_pageUrl($this->totalPages).'">'.$this->config['last'].'</a>';
        }
        /* 上一页 下一页 */
        $prevPage = $this->currentPage - 1;
        $nextPage = $this->currentPage + 1;
        if($prevPage > 0)
        {
            $prevPage = '<a class="prevPage" href="'.$this->get_pageUrl($prevPage).'">'.$this->config['prev'].'</a>';
        }
        else
        {
            $prevPage = '<span class="prevPage">'.$this->config['prev'].'</span>';
        }
        if($nextPage <= $this->totalPages)
        {
            $nextPage = '<a class="nextPage" href="'.$this->get_pageUrl($nextPage).'">'.$this->config['next'].'</a>';
        }
        else
        {
            $nextPage = '<span class="nextPage">'.$this->config['next'].'</span>';
        }
        /* 当前页及附近页 */
        $currentPage = '';
        for($i = $this->nearSize; $i > 0; $i--)
        {
            $_p = $this->currentPage - $i;
            if($_p > 0)
            {
                $currentPage .= '<a class="nearPage" href="'.$this->get_pageUrl($_p).'">'.$_p.'</a>';
            }
        }
        $currentPage .= '<span class="currentPage">'.$this->currentPage.'</span>';
        for ($i = 1; $i < $this->nearSize + 1; $i++)
        {
            $_p = $this->currentPage + $i;
            if ($_p <= $this->totalPages)
            $currentPage .= '<a class="nearPage" href="'.$this->get_pageUrl($_p).'">'.$_p.'</a>';
        }
        $total = '<span class="total">'.L('TOTAL_PAGES').':'.$this->totalPages.' | '.L('TOTAL_ROWS').':'.$this->totalRows.'</span>';
        /* 应用模板 */
        $pageStr = str_replace(
            array(
                '%firstPage%',
                '%prevPage%',
                '%currentPage%',
                '%nextPage%',
                '%lastPage%',
                '%total%'),
            array(
                $firstPage,
                $prevPage,
                $currentPage,
                $nextPage,
                $lastPage,
                $total),
            $this->config['theme']);
        return $pageStr;
    }

    /* 获取limit参数 */
    public function get_limit()
    {
        return ($this->currentPage - 1) * $this->pageSize.','.$this->pageSize;
    }

    /* 获取分页地址 */
    private function get_pageUrl($page)
    {
        if('' != $this->pageUrl)
        {
            return str_replace("_page_", $page, $this->pageUrl);
        }
        $p = C('VAR_PAGE');
        parse_str($_SERVER['QUERY_STRING'], $argument);
        $argument[$p] = $page;
        return $url = $_SERVER['PHP_SELF'].'?'.http_build_query($argument);
    }
}

/*-------------------------实例--------------------------------*
$p = new Page($rowsNum, $pageSize, '?page={page}');//用于动态
$p = new Page($rowsNum, $pageSize, 'list-{page}.html');//用于静态或者伪静态
define('PAGE_LIST', $p->show());
$limit = $p->get_limit();
*/
?>