<%@ taglib prefix="c" uri="http://java.sun.com/jsp/jstl/core" %>
<%@include file="templates/header.jsp" %>
<div class="container-fluid" style="margin-top: 2em">
    <div class="container">
        <h1>Something went wrong <i class="fas fa-exclamation-circle"></i></h1>
        <c:choose>
            <c:when test="${exception != null}">
                <p>Here's all we know</p>
                <div class="text-muted">
                    ${exception.getMessage()}
                    <c:if test="${exception.getCause() != null}">
                        <hr>
                        ${exception.getCause()}
                    </c:if>
                </div>
            </c:when>
            <c:otherwise>
                <p>Something broke, we don't know why, maybe try what you were doing again?</p>
            </c:otherwise>
        </c:choose>
    </div>
</div>
<%@include file="templates/footer.jsp" %>