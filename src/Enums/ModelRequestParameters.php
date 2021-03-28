<?php

namespace RushApp\Core\Enums;

class ModelRequestParameters
{
    /**
     * For server paginate
     * You should always use "paginate" in all requests.
     * You also can use paramName "page" for show current user`s page
     *
     * Example: http://127.0.0.1:8000/test?paginate=2&page=1
     * @var string
     */
    public const PAGINATE = 'paginate';

    /**
     * You should always use "language_id" in all Translations tables.
     * Example: $table->foreign('language_id')->references('id')->on('languages');
     *
     * @var string
     */
    public const LANGUAGE_FOREIGN_KEY = "language_id";

    /**
     * Example: http://127.0.0.1:8000/test?order_by_field=year:desc
     *
     * @var string
     */
    public const ORDER_BY_FIELD = "order_by_field";

    /**
     * Before using this parameter you must be sure that you add this relations in model.
     *
     * Example: http://127.0.0.1:8000/test?with=user:id,email|categories:id,title
     * @var string
     */
    public const WITH = "with";

    /**
     * Example: http://127.0.0.1:8000/test?limit=2
     * @var string
     */
    public const LIMIT = "limit";

    /**
     * Example: http://127.0.0.1:8000/test?selected_fields=year,id,name
     *
     * @var string
     */
    public const SELECTED_FIELDS = "selected_fields";

    /**
     * Example: http://127.0.0.1:8000/test?where_not_null=year,id,name
     *
     * @var string
     */
    public const WHERE_NOT_NULL = "where_not_null";

    /**
     * Example: http://127.0.0.1:8000/test?where_null=year,id,name
     *
     * @var string
     */
    public const WHERE_NULL = "where_null";

    /**
     * Example: http://127.0.0.1:8000/test?where_between=year:2018,2020|create_at:2020-01-01,2021-01-01
     *
     * @var string
     */
    public const WHERE_BETWEEN = "where_between";

    /**
     * Example: http://127.0.0.1:8000/test?where_in=year:2018,2014,2020|user_id:2,2,5,6
     *
     * @var string
     */
    public const WHERE_IN = "where_in";

    /**
     * Example: http://127.0.0.1:8000/test?where_not_in=year:2018,2014,2020|user_id:2,2,5,6
     *
     * @var string
     */
    public const WHERE_NOT_IN = "where_not_in";

    /**
     * Example: http://127.0.0.1:8000/test?offset=5
     *
     * @var string
     */
    public const OFFSET = "offset";

}