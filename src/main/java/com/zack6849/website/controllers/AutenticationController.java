package com.zack6849.website.controllers;

import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
@Controller
public class AutenticationController {

    @GetMapping("/login")
    public String login(Model model){
        model.addAttribute("pageTitle", "Login Required");
        return "login";
    }
}
