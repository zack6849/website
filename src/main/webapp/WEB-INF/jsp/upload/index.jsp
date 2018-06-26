<div th:replace="~{templates/header}"></div>
<div class="container-fluid" id="pageContent">
    <div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
        <h1>Upload <span th:text="${type}"></span>&nbsp;<i class="fas fa-file"></i></h1>
        <form th:action="${endpoint}" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="exampleFormControlFile1">Select a file for upload</label>
                <input type="file" name="file" class="form-control-file" id="exampleFormControlFile1">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>
<div th:replace="~{templates/footer}"></div>