<%@ page import="com.zack6849.website.model.wallpapers.Wallpaper" %>
<%@ page import="com.zack6849.website.services.storage.StorageService" %>
<%@ page import="org.springframework.core.io.Resource" %>
<%@ page import="java.util.Base64" %>
<%@ page import="org.apache.commons.io.FileUtils" %>
<%@include file="../templates/header.jsp" %>
<div class="container-fluid" style="margin-top: 2em">
    <div class="container">
        <%
            StorageService service = (StorageService) request.getAttribute("storage");
            for(Wallpaper w : (Iterable<Wallpaper>) request.getAttribute("wallpapers")){
                Resource r = service.loadAsResource(w.getPath(), service.getProperties().getWallpaperDestination());
                String encoded = new String(Base64.getEncoder().encode(FileUtils.readFileToByteArray(r.getFile())));
                out.println("<img class='img-thumbnail' style='width: 250px; height: 250px%' src='data:image/png;base64," + encoded + "'>");
            }
        %>
    </div>
</div>
<%@include file="../templates/footer.jsp" %>