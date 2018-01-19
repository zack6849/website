package com.zack6849.website.controllers;

import com.zack6849.website.model.wallpapers.Wallpaper;
import com.zack6849.website.repository.WallpaperRepository;
import com.zack6849.website.services.storage.StorageService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.nio.file.Path;
import java.nio.file.Paths;

@Controller
@RequestMapping("/wallpapers")
public class WallpaperController {

    @Autowired
    private WallpaperRepository repository;
    @Autowired
    private StorageService storageService;

    @PostMapping("/add")
    public @ResponseBody String addNewWallpaper(@RequestParam("file") MultipartFile file) {
        if (file.getContentType().contains("image/")) {
            Path destination = Paths.get(storageService.getProperties().getWallpaperDestination());
            Path result = storageService.store(file, destination);
            Wallpaper wallpaper = new Wallpaper();
            wallpaper.setName(file.getOriginalFilename());
            wallpaper.setPath(destination.relativize(result).toString());
            repository.save(wallpaper);

            return "Saved";
        }
        return "Not an image.";
    }

    @GetMapping("/add")
    public String index(Model model){
        model.addAttribute("type", "Wallpaper");
        model.addAttribute("endpoint", "add");
        return "upload/index";
    }

    @GetMapping("")
    public String list(Model model){
        model.addAttribute("wallpapers", repository.findAll());
        model.addAttribute("storage", storageService);
        return "wallpaper/list";
    }
}
