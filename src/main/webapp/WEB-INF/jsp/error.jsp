<div th:replace="~{templates/header}"></div>
<div class="container-fluid" id="pageContent">
    <div class="container">

        <h1><span th:text="${status}"></span> - Something went wrong <i class="fas fa-exclamation-circle"></i></h1>
        <p th:utext="${message}">Error java.lang.NullPointerException</p>
        <a href="/" th:href="@{/}">Back to Home Page</a>
    </div>
</div>
<div th:replace="~{templates/footer}"></div>