<%@ taglib prefix="th" uri="http://www.springframework.org/tags/form" %>
<div th:replace="~{templates/header}"></div>
<div id="pageContent">
    <div class="container">
        <div class="row align-items-center justify-content-center" style="padding-top: 2em;">
            <div class="col-sm-5 align-self-center">
                Hi There, <span th:text="${principal.name}">Undefined!</span>!
            </div>
        </div>
    </div>
</div>
<div th:replace="~{templates/footer}"></div>