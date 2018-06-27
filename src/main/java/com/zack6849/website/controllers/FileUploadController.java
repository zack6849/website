package com.zack6849.website.controllers;

import com.zack6849.website.services.storage.StorageService;
import com.zack6849.website.services.storage.exception.StorageException;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.core.io.Resource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.io.IOException;
import java.net.URLConnection;
import java.nio.file.Path;


@Controller
public class FileUploadController {
    @Autowired
    private StorageService storageService;

    @GetMapping("/upload")
    public String index(Model model){
        model.addAttribute("type", "File");
        model.addAttribute("endpoint", "upload");
        return "upload/index";
    }

    @PostMapping("/upload/upload")
    public String handleFileUpload(@RequestParam("file") MultipartFile file, RedirectAttributes redirectAttributes){
        Path result = storageService.store(file);
        return "redirect:/uploads/" + result.getFileName();
    }

    @GetMapping("/uploads/{filename:.+}")
    @ResponseBody
    public ResponseEntity<?>  serveFile(@PathVariable String filename) throws IOException {
        Resource file = storageService.loadAsResource(filename);
        String disposition = String.format("filename=\"%s\"", filename);
        String type = URLConnection.guessContentTypeFromStream(file.getInputStream());
        if(type == null){
            type = URLConnection.guessContentTypeFromStream(file.getInputStream());
            if(type == null){
                type = "application/octet-stream";
                disposition = String.format("attachment; filename=\"%s\"", filename);
            }
        }
        return ResponseEntity.ok().header(HttpHeaders.CONTENT_DISPOSITION, disposition).contentType(MediaType.parseMediaType(type)).body(file);
    }

    @ExceptionHandler(StorageException.class)
    public String handleException(StorageException ex, Model model){
        model.addAttribute("exception", ex);
        return "error.jsp";
    }

}
