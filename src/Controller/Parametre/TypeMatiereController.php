<?php

namespace App\Controller\Parametre;

use App\Entity\TypeMatiere;
use App\Form\TypeMatiereType;
use App\Repository\TypeMatiereRepository;
use App\Service\ActionRender;
use App\Service\FormError;
use Doctrine\ORM\EntityManagerInterface;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parametre/type/matiere')]
class TypeMatiereController extends AbstractController
{
    #[Route('/', name: 'app_parametre_type_matiere_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create()
        ->add('code', TextColumn::class, ['label' => 'Code'])
        ->add('libelle', TextColumn::class, ['label' => 'Libellé'])
        ->createAdapter(ORMAdapter::class, [
            'entity' => TypeMatiere::class,
        ])
        ->setName('dt_app_parametre_type_matiere');

        $renders = [
            'edit' =>  new ActionRender(function () {
                return true;
            }),
            'delete' => new ActionRender(function () {
                return true;
            }),
        ];


        $hasActions = false;

        foreach ($renders as $_ => $cb) {
            if ($cb->execute()) {
                $hasActions = true;
                break;
            }
        }

        if ($hasActions) {
            $table->add('id', TextColumn::class, [
                'label' => 'Actions'
                , 'orderable' => false
                ,'globalSearchable' => false
                ,'className' => 'grid_row_actions'
                , 'render' => function ($value, TypeMatiere $context) use ($renders) {
                    $options = [
                        'default_class' => 'btn btn-sm btn-clean btn-icon mr-2 ',
                        'target' => '#modal-lg',

                        'actions' => [
                            'edit' => [
                                'url' => $this->generateUrl('app_parametre_type_matiere_edit', ['id' => $value]),
                                'ajax' => true,
                                'stacked' => false,
                                'icon' => '%icon% bi bi-pen',
                                'attrs' => ['class' => 'btn-main'],
                                'render' => $renders['edit']
                        ],
                        'delete' => [
                            'target' => '#modal-small',
                            'url' => $this->generateUrl('app_parametre_type_matiere_delete', ['id' => $value]),
                            'ajax' => true,
                            'stacked' => false,
                            'icon' => '%icon% bi bi-trash',
                            'attrs' => ['class' => 'btn-danger'],
                            'render' => $renders['delete']
                        ]
                    ]

                    ];
                    return $this->renderView('_includes/default_actions.html.twig', compact('options', 'context'));
                }
            ]);
        }


        $table->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }


        return $this->render('parametre/type_matiere/index.html.twig', [
            'datatable' => $table
        ]);
    }


    #[Route('/new', name: 'app_parametre_type_matiere_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FormError $formError): Response
    {
        $typeMatiere = new TypeMatiere();
        $form = $this->createForm(TypeMatiereType::class, $typeMatiere, [
            'method' => 'POST',
            'action' => $this->generateUrl('app_parametre_type_matiere_new')
        ]);
        $form->handleRequest($request);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();

        if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('app_parametre_type_matiere_index');




            if ($form->isValid()) {

                $entityManager->persist($typeMatiere);
                $entityManager->flush();

                $data = true;
                $message       = 'Opération effectuée avec succès';
                $statut = 1;
                $this->addFlash('success', $message);


            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                  $this->addFlash('warning', $message);
                }

            }


            if ($isAjax) {
                return $this->json( compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }


        }

        return $this->render('parametre/type_matiere/new.html.twig', [
            'type_matiere' => $typeMatiere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/show', name: 'app_parametre_type_matiere_show', methods: ['GET'])]
    public function show(TypeMatiere $typeMatiere): Response
    {
        return $this->render('parametre/type_matiere/show.html.twig', [
            'type_matiere' => $typeMatiere,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_parametre_type_matiere_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeMatiere $typeMatiere, EntityManagerInterface $entityManager, FormError $formError): Response
    {

        $form = $this->createForm(TypeMatiereType::class, $typeMatiere, [
            'method' => 'POST',
            'action' => $this->generateUrl('app_parametre_type_matiere_edit', [
                    'id' =>  $typeMatiere->getId()
            ])
        ]);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();


        $form->handleRequest($request);

       if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('app_parametre_type_matiere_index');




            if ($form->isValid()) {

                $entityManager->persist($typeMatiere);
                $entityManager->flush();

                $data = true;
                $message       = 'Opération effectuée avec succès';
                $statut = 1;
                $this->addFlash('success', $message);


            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                  $this->addFlash('warning', $message);
                }

            }

            if ($isAjax) {
                return $this->json( compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }

        }

        return $this->render('parametre/type_matiere/edit.html.twig', [
            'type_matiere' => $typeMatiere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_parametre_type_matiere_delete', methods: ['DELETE', 'GET'])]
    public function delete(Request $request, TypeMatiere $typeMatiere, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder()
            ->setAction(
                $this->generateUrl(
                'app_parametre_type_matiere_delete'
                ,   [
                        'id' => $typeMatiere->getId()
                    ]
                )
            )
            ->setMethod('DELETE')
        ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = true;
            $entityManager->remove($typeMatiere);
            $entityManager->flush();

            $redirect = $this->generateUrl('app_parametre_type_matiere_index');

            $message = 'Opération effectuée avec succès';

            $response = [
                'statut'   => 1,
                'message'  => $message,
                'redirect' => $redirect,
                'data' => $data
            ];

            $this->addFlash('success', $message);

            if (!$request->isXmlHttpRequest()) {
                return $this->redirect($redirect);
            } else {
                return $this->json($response);
            }
        }

        return $this->render('parametre/type_matiere/delete.html.twig', [
            'type_matiere' => $typeMatiere,
            'form' => $form,
        ]);
    }
}
