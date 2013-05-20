<?php
/**
 * -----------------------------------------------------------------------------
 * Paginator
 * -----------------------------------------------------------------------------
 *
 * @author      Mardix (http://twitter.com/mardix)
 * @copyright   (c) 2012-2013 Mardix (http://github.com/mardix)
 * @license     MIT
 * -----------------------------------------------------------------------------
 *
 * ABOUT
 * -----
 * Paginator is a simple class that allows you to create pagination for your application.
 * It doesn't require any database connection. It is compatible with Twitter's Bootstrap Framework,
 * by using the CSS class pagination in the example folder
 * So it can be implemented quickly in your existing settings.
 *
 *
 * How it works
 * -----------
 * It reads the $url ( http://xyz.x/page/253 ) that was provided and based on the regexp pattern (ie: /page/(:num))
 * it extract the page number and build the pagination for all the page numbers. If the page number does't exist, i will create one
 * for you based on the pattern
 *
 * About $pattern (:num)
 * -----------
 * (:num) is our regex pattern to capture the page number and pass it to generate the pagination.
 * It is require to catch the page number properly
 *
 *  /page/(:num) , will capture page in this pattern http://xyz.com/page/252
 *
 *  page=(:num) , will capture the pattern http://xyz.com/?page=252
 *
 *  Any other regexp pattern will work also
 *
 * When a query url is set without the page number, automatically based on the page pattern, the page number will be added
 * i.e:
 *     $url = http://xyz.com/?q=boom
 *     $pattern = page=(:num)
 *
 *     the page number will be added as so at the end of the query
 *     http://xyz.com/?q=boom&page=2
 *
 *
 *
 * Example
 * With friendly url:
 * ------------------
 *      $siteUrl = "http://www.givemebeats.net/buy-beats/Hip-Hop-Rap/page/4/";
 *      $pattern = "/page/(:num)";
 *      $total = 225;
 *      $Paginator = new Paginator($siteUrl,$pattern);
 *      $Pagination = $Paginator($total);
 *      print($Pagination);
 *
 *
 * With non friendly url:
 * ------------------
 *      $siteUrl = "http://www.givemebeats.net/buy-beats/?genre=Hip-Hop-Rap&page=4";
 *      $pattern = "page=(:num)";
 *      $total = 225;
 *      $Paginator = new Paginator($siteUrl,$pattern);
 *      $Pagination = $Paginator($total);
 *      print($Pagination);
 *
 *
 * Quick way:
 * ---------
 *      Paginator::create($pattern,$total);
 *
 *
 * Major Methods
 * -----------------
 *
 * - __construct()                        : Instantiate the class
 * - path($url,$pattern)  : To set the url that will be used to create the pagination. $pattern is a regex to catch the page number in the $url
 * - total($total)           : Set the total items. It is required so it create the proper page count etc
 * - size($ipp)                : Total items to display in your results page. This count will allow it to properly count pages
 * - displays($nav)              : Crete the size of the pagination like [1][2][3][4][next]
 * - titles(Prev,Next)          : To set the action next and previous
 * - toArray($total)                 : Return the pagination in array. Use it if you want to use your own template to generate the pagination in HTML
 * - render($total)                  : Return the pagination in HTML format
 *
 *
 *
 * Other methods to access and update data before rendering
 *
 * - current()                   : Return the current page number
 * - pages()                    : Return the total pages
 * - startCount()                    : The start count.
 * - endCount()                      : The end count
 * - sqlOffset()                     : When using SQL query, you can use this method to give you the limit count like: 119,10 which will be used in "LIMIT 119,10"
 * - size()                  : Return the total items per page
 * - currentUrl()                : Return the full url of the current page including the page number
 * - prevUrl()               : Return the full url of the previous page including the page number
 * - nextUrl()                   : Return the full url of the next page including the page number
 *
 */

namespace Pagon;

use Pagon\Fiber;

/**
 * Paginator
 * functional usage for generate Paginator HTML code
 *
 * @package Pagon
 */
class Paginator extends Fiber
{
    /**
     * Holds the template url
     *
     * @var string
     */
    protected $templateUrl = "";


    /**
     * Create the Paginator with the Url::current. It's a shortcut to quickly build it with the request URI
     *
     * @param string $pattern        - a regex pattern that will match the url and extract the page number
     * @param int    $total          - Total items found
     * @param int    $size           - Total items per page
     * @param int    $displays       - The displays size
     * @return Paginator
     */
    public static function create($pattern = "/page/(:num)", $total = 0, $size = 10, $displays = 10)
    {
        return new self(Url::current(), $pattern, $total, $size, $displays);
    }

    /**
     * Constructor
     *
     * @param string $url         - The url of the pagination
     * @param string $pattern     - a regex pattern that will match the url and extract the page number
     * @param int    $total       - Total items found
     * @param int    $size        - Total items per page
     * @param int    $displays    - The display size
     */
    public function __construct($url = "", $pattern = "/page/(:num)", $total = 0, $size = 10, $displays = 10)
    {
        if ($url) {
            $this->url($url, $pattern);
        }
        $this->total($total);
        $this->size($size);
        $this->displays($displays);
        $this->titles();
    }

    /**
     * Set the URL, automatically it will parse every thing to it
     *
     * @param string $url
     * @param string $pattern
     * @return Paginator
     */
    public function url($url, $pattern = "/page/(:num)")
    {
        $_pattern = str_replace("(:num)", "([0-9]+)", $pattern);
        preg_match("~$_pattern~i", $url, $m);
        /**
         * No match found.
         * We'll add the pagination in the url, so this way it can be ready for next pages.
         * This way a url http://xyz.com/?q=boom , becomes http://xyz.com/?q=boom&page=2
         */
        if (count($m) == 0) {
            $pag_ = str_replace("(:num)", 0, $pattern);

            // page pattern contain the equal sign, we'll add it to the query ?page=123
            if (strpos($pattern, "=") !== false) {
                if (strpos($url, "?") !== false) {
                    $url .= "&" . $pag_;
                } else {
                    $url .= "?" . $pag_;
                }
                return $this->url($url, $pattern);
            } else if (strpos($pattern, "/") !== false) { //Friendly url : /page/123
                if (strpos($url, "?") !== false) {
                    list($segment, $query) = explode("?", $url, 2);
                    if (preg_match("/\/$/", $segment)) {
                        $url = $segment . (preg_replace("/^\//", "", $pag_));
                        $url .= ((preg_match("/\/$/", $pag_)) ? "" : "/") . "?{$query}";
                    } else {
                        $url = $segment . $pag_;
                        $url .= ((preg_match("/\/$/", $pag_)) ? "" : "/") . "?{$query}";
                    }
                } else {
                    if (preg_match("/\/$/", $segment)) {
                        $url .= (preg_replace("/^\//", "", $pag_));
                    } else {
                        $url .= $pag_;
                    }
                }
                return $this->url($url, $pattern);
            }
        }
        $match = current($m);
        $last = end($m);
        $page = $last ? $last : 1;

        // TemplateUrl will be used to create all the page numbers 
        $this->templateUrl = str_replace($match, preg_replace("/[0-9]+/", "(#pageNumber)", $match), $url);
        $this->current($page);
        return $this;
    }

    /**
     * To set the previous and next title
     *
     * @param string $prev : Prev | &laquo; | &larr;
     * @param string $next : Next | &raquo; | &rarr;
     * @return Paginator
     */
    public function titles($prev = "Prev", $next = "Next")
    {
        $this->injectors["prevTitle"] = $prev;
        $this->injectors["nextTitle"] = $next;
        return $this;
    }

    /**
     * Set the total items. It will be used to determined the size of the pagination set
     *
     * @param int $count
     * @return Paginator
     */
    public function total($count = null)
    {
        if ($count === null) {
            return $this->injectors['total'];
        } else {
            $this->injectors["total"] = $count;
            return $this;
        }
    }

    /**
     * Set the items per page
     *
     * @param int $ipp
     * @return Paginator|int
     */
    public function size($ipp = null)
    {
        if ($ipp === null) {
            return $this->injectors['size'];
        } else {
            $this->injectors["size"] = $ipp;
            return $this;
        }
    }

    /**
     * Set the current page
     *
     * @param int $page
     * @return Paginator
     */
    public function current($page = null)
    {
        if ($page === null) {
            return $this->injectors['current'];
        } else {
            $this->injectors["current"] = $page;
            return $this;
        }
    }

    /**
     * Get the pagination start count
     *
     * @return int
     */
    public function startCount()
    {
        return (int)($this->size() * ($this->current() - 1));
    }

    /**
     * Get the pagination end count
     *
     * @return int
     */
    public function endCount()
    {
        return (int)((($this->size() - 1) * $this->current()) + $this->current());
    }

    /**
     * Return the offset for sql queries, specially
     *
     * @return START,LIMIT
     *
     * @tip  : SQL tip. It's best to do two queries one with SELECT COUNT(*) FROM tableName WHERE X
     *       set the settotal()
     */
    public function sqlOffset()
    {
        return $this->startCount() . "," . $this->size();
    }

    /**
     * Get the total pages
     *
     * @return int
     */
    public function pages()
    {
        return @ceil($this->total() / $this->size());
    }

    /**
     * Get the navigation size
     *
     * @param int $set
     * @return int
     */
    public function displays($set = null)
    {
        if ($set === null) {
            return $this->injectors["displays"];
        } else {
            $this->injectors["displays"] = $set;
            return $this;
        }
    }

    /**
     * Get the current page url
     *
     * @return string
     */
    public function currentUrl()
    {
        return $this->parseUrl($this->current());
    }

    /**
     * Get the previous page url if it exists
     *
     * @return string
     */
    public function prevUrl()
    {
        $prev = $this->current() - 1;
        return ($prev > 0 && $prev < $this->pages()) ? $this->parseUrl($prev) : "";
    }

    /**
     * Get the next page url if it exists
     *
     * @return string
     */
    public function nextUrl()
    {
        $next = $this->current() + 1;
        return ($next <= $this->pages()) ? $this->parseUrl($next) : "";
    }

    /*******************************************************************************/

    /**
     * toArray() export the pagination into an array. This array can be used for your own template or for other usafe
     *
     * @param int $total - the total Items found
     * @return Array
     *          Array(
     *          array(
     *                "page", // the page number
     *                "label", // the label for the page number
     *                "url", // the url
     *                "current" // bool  set if page is current or not
     *          )
     *      )
     */
    public function toArray($total = 0)
    {
        $navigation = array();
        if ($total) {
            $this->total($total);
        }

        $pages = $this->pages();
        $displays = $this->displays();
        $current = $this->current();

        if ($pages) {

            $halfSet = @ceil($displays / 2);
            $start = 1;
            $end = ($pages < $displays) ? $pages : $displays;

            $usePrevNextNav = ($pages > $displays) ? true : false;

            if ($current >= $displays) {
                $start = $current - $displays + $halfSet + 1;
                $end = $current + $halfSet - 1;
            }
            if ($end > $pages) {
                $s = $pages - $displays;
                $start = $s ? $s : 1;
                $end = $pages;
            }
            // Previous   
            $prev = $current - 1;
            if ($current >= $displays && $usePrevNextNav) {
                $navigation[] = array(
                    "page"    => $prev,
                    "label"   => $this->prevTitle,
                    "url"     => $this->parseUrl($prev),
                    "current" => false
                );
            }
            // All the pages
            for ($i = $start; $i <= $end; $i++) {
                $navigation[] = array(
                    "page"    => $i,
                    "label"   => $i,
                    "url"     => $this->parseUrl($i),
                    "current" => ($i == $current) ? true : false,
                );
            }
            // Next 
            $next = $current + 1;
            if ($next < $pages && $end < $pages && $usePrevNextNav) {
                $navigation[] = array(
                    "page"    => $next,
                    "label"   => $this->nextTitle,
                    "url"     => $this->parseUrl($next),
                    "current" => false
                );
            }
        }
        return $navigation;
    }


    /**
     * Render the paginator in HTML format
     *
     * @param int    $total             - The total Items
     * @param string $paginationClsName - The class name of the pagination
     * @param string $wrapTag
     * @param string $listTag
     * @return string
     * <code>
     * <div class='pagination'>
     *      <ul>
     *          <li>1</li>
     *          <li class='active'>2</li>
     *          <li>3</li>
     *      <ul>
     * </div>
     * </code>
     */
    public function render($total = 0, $paginationClsName = "pagination", $wrapTag = "ul", $listTag = "li")
    {
        $this->listTag = $listTag;
        $this->wrapTag = $wrapTag;
        $pagination = "";
        foreach ($this->toArray($total) as $page) {
            $pagination .= $this->wrap($this->link($page["url"], $page["label"]), $page["current"], false);
        }
        return
            "<div class=\"{$paginationClsName}\">
                <{$this->wrapTag}>{$pagination}</{$this->wrapTag}>
            </div>";
    }

    /**
     * Parse a page number in the template url
     *
     * @param int $pageNumber
     * @return string
     */
    protected function parseUrl($pageNumber)
    {
        return str_replace("(#pageNumber)", $pageNumber, $this->templateUrl);
    }

    /**
     * To create an <a href> link
     *
     * @param string $url
     * @param string $txt
     * @return string
     */
    protected function link($url, $txt)
    {
        return "<a href=\"{$url}\">{$txt}</a>";
    }

    /**
     * Create a wrap list, ie: <li></li>
     *
     * @param string $html
     * @param bool   $isActive   - To set the active class in this element
     * @param bool   $isDisabled - To set the disabled class in this element
     * @return string
     */
    protected function wrap($html, $isActive = false, $isDisabled = false)
    {
        $class = array();
        $isActive && $class[] = 'active';
        $isDisabled && $class[] = 'disabled';

        return "<{$this->listTag} " . ($class ? 'class="' . join(' ', $class) . '"' : "") . ">{$html}</{$this->listTag}>\n";
    }

    /**
     * Rendor to HTML
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
