<?php
/**
 * PaginationHelper - Sistema de paginación
 *
 * Maneja la lógica de paginación para listados largos
 */

class PaginationHelper
{
    private int $currentPage;
    private int $itemsPerPage;
    private int $totalItems;
    private string $baseUrl;
    private array $queryParams;

    /**
     * Constructor
     *
     * @param int $totalItems Total de elementos
     * @param int $itemsPerPage Elementos por página
     * @param int $currentPage Página actual
     * @param string $baseUrl URL base
     * @param array $queryParams Parámetros adicionales
     */
    public function __construct(
        int $totalItems,
        int $itemsPerPage = 20,
        int $currentPage = 1,
        string $baseUrl = '',
        array $queryParams = []
    ) {
        $this->totalItems = $totalItems;
        $this->itemsPerPage = $itemsPerPage;
        $this->currentPage = max(1, min($currentPage, $this->getTotalPages()));
        $this->baseUrl = $baseUrl ?: $_SERVER['REQUEST_URI'];
        $this->queryParams = $queryParams;
    }

    /**
     * Obtener offset para la consulta SQL
     *
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    /**
     * Obtener límite para la consulta SQL
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * Obtener número total de páginas
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        return ceil($this->totalItems / $this->itemsPerPage);
    }

    /**
     * Verificar si hay página anterior
     *
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Verificar si hay página siguiente
     *
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }

    /**
     * Obtener página anterior
     *
     * @return int
     */
    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    /**
     * Obtener página siguiente
     *
     * @return int
     */
    public function getNextPage(): int
    {
        return min($this->getTotalPages(), $this->currentPage + 1);
    }

    /**
     * Generar URL para una página específica
     *
     * @param int $page Número de página
     * @return string
     */
    public function getPageUrl(int $page): string
    {
        $params = array_merge($this->queryParams, ['page' => $page]);
        return $this->baseUrl . '?' . http_build_query($params);
    }

    /**
     * Obtener rango de páginas para mostrar
     *
     * @param int $maxPages Máximo número de páginas a mostrar
     * @return array
     */
    public function getPageRange(int $maxPages = 7): array
    {
        $totalPages = $this->getTotalPages();
        $half = floor($maxPages / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($totalPages, $start + $maxPages - 1);

        // Ajustar si estamos cerca del final
        if ($end - $start + 1 < $maxPages) {
            $start = max(1, $end - $maxPages + 1);
        }

        $pages = [];
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        return $pages;
    }

    /**
     * Renderizar HTML de paginación
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->getTotalPages() <= 1) {
            return '';
        }

        $html = '<nav aria-label="Paginación"><ul class="pagination justify-content-center">';

        // Página anterior
        if ($this->hasPreviousPage()) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->getPreviousPage()) . '" aria-label="Anterior">';
            $html .= '<span aria-hidden="true">&laquo;</span></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        // Páginas
        foreach ($this->getPageRange() as $page) {
            $active = $page === $this->currentPage ? ' active' : '';
            $html .= '<li class="page-item' . $active . '">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($page) . '">' . $page . '</a></li>';
        }

        // Página siguiente
        if ($this->hasNextPage()) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->getNextPage()) . '" aria-label="Siguiente">';
            $html .= '<span aria-hidden="true">&raquo;</span></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Obtener información de paginación
     *
     * @return array
     */
    public function getInfo(): array
    {
        $startItem = $this->getOffset() + 1;
        $endItem = min($this->getOffset() + $this->itemsPerPage, $this->totalItems);

        return [
            'current_page' => $this->currentPage,
            'total_pages' => $this->getTotalPages(),
            'items_per_page' => $this->itemsPerPage,
            'total_items' => $this->totalItems,
            'start_item' => $startItem,
            'end_item' => $endItem,
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->hasNextPage(),
            'previous_page' => $this->getPreviousPage(),
            'next_page' => $this->getNextPage()
        ];
    }

    /**
     * Crear instancia desde parámetros GET
     *
     * @param int $totalItems Total de elementos
     * @param int $itemsPerPage Elementos por página
     * @param array $excludeParams Parámetros a excluir de la URL
     * @return self
     */
    public static function fromGetParams(
        int $totalItems,
        int $itemsPerPage = 20,
        array $excludeParams = ['page']
    ): self {
        $currentPage = intval($_GET['page'] ?? 1);
        $baseUrl = self::getCurrentUrl($excludeParams);
        $queryParams = $_GET;

        foreach ($excludeParams as $param) {
            unset($queryParams[$param]);
        }

        return new self($totalItems, $itemsPerPage, $currentPage, $baseUrl, $queryParams);
    }

    /**
     * Obtener URL actual sin ciertos parámetros
     *
     * @param array $excludeParams Parámetros a excluir
     * @return string
     */
    private static function getCurrentUrl(array $excludeParams = []): string
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        $query = [];

        if (isset($url['query'])) {
            parse_str($url['query'], $query);
        }

        foreach ($excludeParams as $param) {
            unset($query[$param]);
        }

        $queryString = http_build_query($query);
        return $url['path'] . ($queryString ? '?' . $queryString : '');
    }
}