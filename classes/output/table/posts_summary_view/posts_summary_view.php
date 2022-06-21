<?php

namespace format_cop\output\table\posts_summary_view;

interface posts_summary_view
{
    public function get_default_order(): string;
    public function get_default_column(): string;
    public function get_sql(): array;
    public function get_title(): string;
    public function get_headers(): array;
    public function get_columns(): array;
}