<%@ taglib prefix="th" uri="http://www.springframework.org/tags/form" %>
<div th:replace="~{templates/header}"></div>
<div id="pageContent">
    <div class="container">
        <div class="row align-items-center justify-content-center" style="padding-top: 2em;">
            <div class="col-sm-5 align-self-center">
                <div th:if="${param.error}" class="alert alert-danger" role="alert">
                    Invalid username and password.
                </div>
                <div th:if="${param.logout}" class="alert alert-success" role="alert">
                    You have been logged out.
                </div>
                <div class="card" style="padding: 15px;">
                    <div class="card-title">Login</div>
                    <div class="card-subtitle text-muted">Authentication is required, please login to continue.</div>
                    <div class="card-body">
                        <form th:action="@{/login}" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input class="form-control"  id="username" type="text" name="username" placeholder="Enter your username">
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input class="form-control"  id="password" type="password" name="password" placeholder="Enter your password">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div th:replace="~{templates/footer}"></div>