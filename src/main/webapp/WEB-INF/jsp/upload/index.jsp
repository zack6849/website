<%@include file="../templates/header.jsp" %>
<div class="container-fluid">
    <div class="container">
        <h1>Upload File</h1>
        <form action="/upload" method="post" enctype="multipart/form-data">
            <input type="file" name="file">
            <input type="submit">
        </form>
    </div>
</div>
<%@include file="../templates/footer.jsp" %>