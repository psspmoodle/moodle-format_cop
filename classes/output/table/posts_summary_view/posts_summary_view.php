<?php

namespace format_cop\output\table\posts_summary_view;

abstract class posts_summary_view
{
    protected array $columns;

    protected string $default_column;

    protected string $default_order;

    protected array $headers;

    protected array $sql;

    protected string $title;

    protected array $countsql;

    protected function __construct()
    {
        $this->countsql = [];
    }

    abstract protected function set_sql($cmids);

    public function get_sql(): array
    {
        return $this->sql;
    }

    public function get_count_sql(): array
    {
        return $this->countsql;
    }

    /**
     * @return string
     */
    public function get_default_column(): string
    {
        return $this->default_column;
    }

    /**
     * @return string
     */
    public function get_default_order(): string
    {
        return $this->default_order;
    }

    /**
     * @return string
     */
    public function get_title(): string
    {
        return $this->title;
    }

    /**
     * @return string[]
     */
    public function get_headers(): array
    {
        return $this->headers;
    }

    /**
     * @return string[]
     */
    public function get_columns(): array
    {
        return $this->columns;
    }
}