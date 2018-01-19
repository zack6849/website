<%@include file="../templates/header.jsp" %>
<div class="container-fluid" style="margin-top: 2em">
    <div class="container">
        <h1>Upload ${type}&nbsp;<i class="fas fa-file"></i></h1>
        <form action="${endpoint}" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="exampleFormControlFile1">Select a file for upload</label>
                <input type="file" name="file" class="form-control-file" id="exampleFormControlFile1">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>
<%@include file="../templates/footer.jsp" %>